<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PixupController extends Controller
{
    /**
     * Gera o Token de Acesso da Pixup
     */
    private static function getToken($config)
    {
        if (!$config || empty($config->pixup_client_id) || empty($config->pixup_client_secret)) {
            Log::error('Pixup: Credenciais ausentes no banco de dados.');
            return null;
        }

        $credentials = base64_encode($config->pixup_client_id . ':' . $config->pixup_client_secret);

        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'accept' => 'application/json',
            ])->post($config->pixup_uri . '/oauth/token');

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        Log::error('Pixup Token Error: ' . $response->body());
        return null;
    }

    /**
     * Gera o QR Code para o Front-end - VERSÃO BLINDADA
     */
    public function generateQRCode(Request $request)
    {
        try {
            $config = Gateway::where('slug', 'pixup')->orWhere('id', 1)->first();
            $token = self::getToken($config);
            
            if (!$token) {
                return response()->json(['error' => 'Falha na autenticação com a Pixup.', 'status' => false], 200);
            }

            $user = auth('api')->user();
            
            // Limpa o valor para garantir formato float puro
            $amount = floatval(str_replace(['.', ','], ['', '.'], $request->amount));
            
            if ($amount < 1) {
                return response()->json(['error' => 'Valor mínimo não atingido.', 'status' => false], 200);
            }

            $external_id = uniqid('pix_');

            // 1. OBRIGATÓRIO: Salva no banco de dados ANTES de pedir o PIX
            $createDeposit = Deposit::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'status' => 0,
                'gateway' => 'pixup',
                'hash' => $external_id,
                'payment_id' => $external_id // Grava em ambos para garantir a busca no webhook
            ]);

            if (!$createDeposit) {
                Log::error("ERRO CRÍTICO: Não foi possível gravar o depósito no banco. User: {$user->id}");
                return response()->json(['error' => 'Erro interno ao preparar depósito.', 'status' => false], 200);
            }

            // 2. Só solicita o QR Code se o registro no banco foi confirmado
            $response = Http::withOptions(['verify' => false])
                ->withToken($token)
                ->post($config->pixup_uri . '/pix/qrcode', [
                    'amount' => $amount,
                    'external_id' => $external_id,
                    'postbackUrl' => url('/api/pixup/webhook'),
                    'payerQuestion' => 'Deposito ID ' . $user->id,
                    'payer' => [
                        'name' => $user->name ?? 'Usuario',
                        'document' => preg_replace('/[^0-9]/', '', $user->cpf ?? '00000000000'), 
                        'email' => $user->email
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'status' => true,
                    'qrcode' => $data['qrcode'] ?? '',
                    'idTransaction' => $data['transactionId'] ?? $external_id,
                ], 200);
            }

            // Caso a API da Pixup falhe, removemos o registro pendente para evitar inconsistência
            $createDeposit->delete();
            Log::error('Pixup API Error: ' . $response->body());
            return response()->json(['error' => 'Serviço de PIX indisponível. Tente novamente.', 'status' => false], 200);
            
        } catch (\Exception $e) {
            Log::error('Exceção ao gerar PIX Pixup: ' . $e->getMessage());
            return response()->json(['error' => 'Erro interno de processamento.', 'status' => false], 200);
        }
    }

    /**
     * Webhook (Callback) da Pixup - Processa o pagamento
     */
    public function webhook(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('Recebido Webhook Pixup: ', $payload);
            
            $data = $payload['requestBody'] ?? null;

            if ($data) {
                $status = strtoupper($data['status'] ?? '');
                $type = strtoupper($data['transactionType'] ?? '');
                $external_id = $data['external_id'] ?? null;
                $amount = floatval($data['amount'] ?? 0);

                if ($type === 'RECEIVEPIX' && $status === 'PAID') {
                    
                    // BUSCA CORINGA: Procura por hash ou payment_id sem trava de status inicial
                    $deposit = Deposit::where(function($query) use ($external_id) {
                        $query->where('hash', $external_id)
                              ->orWhere('payment_id', $external_id);
                    })->first();

                    if ($deposit && $deposit->status == 0) {
                        
                        $deposit->update([
                            'status' => 1,
                            'payment_id' => $data['transactionId'] ?? $external_id
                        ]);

                        $wallet = Wallet::where('user_id', $deposit->user_id)->first();
                        
                        if ($wallet) {
                            $wallet->increment('balance', $amount);
                            Log::info("SUCESSO ABSOLUTO: Saldo creditado. User: {$deposit->user_id}, Valor: {$amount}");
                            return response()->json(['message' => 'OK'], 200);
                        } else {
                            Log::error("ERRO: Wallet não encontrada para o usuário {$deposit->user_id}");
                        }
                        
                    } else if ($deposit && $deposit->status == 1) {
                        Log::info("AVISO: Depósito {$external_id} já processado anteriormente.");
                        return response()->json(['message' => 'ALREADY_PAID'], 200);
                    } else {
                        Log::error("ERRO CRÍTICO: O código {$external_id} não existe na tabela DEPOSITS após busca completa.");
                    }
                }
            }
            
            return response()->json(['message' => 'Evento ignorado'], 200);

        } catch (\Exception $e) {
            Log::error('Erro Fatal Webhook Pixup: ' . $e->getMessage());
            return response()->json(['error' => 'Falha interna'], 500);
        }
    }
}