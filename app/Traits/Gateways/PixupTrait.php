<?php

namespace App\Traits\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Gateway;
use App\Models\Deposit;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

trait PixupTrait
{
    /**
     * GERA O TOKEN OAUTH2
     */
    private static function getPixupToken()
    {
        try {
            $gateway = Gateway::first();
            if (!$gateway) return null;

            $credentials = base64_encode($gateway->pixup_client_id . ':' . $gateway->pixup_client_secret);
            
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Basic ' . $credentials,
                    'Accept' => 'application/json'
                ])->post('https://api.pixupbr.com/v2/oauth/token');

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error("PixUP Auth Erro: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("PixUP Auth Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * GERA O QR CODE PARA DEPÓSITO (Com Sequestro de Resposta)
     */
    public static function requestQrcodePixup(Request $request)
    {
        try {
            $user = auth('api')->user();
            $amount = (float) $request->amount;
            
            // Geramos nosso identificador único
            $transactionId = Str::uuid()->toString();

            $token_api = self::getPixupToken();
            if (!$token_api) {
                return response()->json(['status' => false, 'error' => 'Erro de conexão com o banco de pagamentos.']);
            }

            $response = Http::withToken($token_api)
                ->withoutVerifying()
                ->post('https://api.pixupbr.com/v2/pix/qrcode', [
                    'amount' => $amount,
                    'external_id' => $transactionId,
                    'postbackUrl' => url('/api/gateways/pixup/webhook'),
                    'payer' => [
                        'name' => $user->name,
                        'document' => preg_replace('/[^0-9]/', '', $user->cpf ?? '00000000000'),
                        'email' => $user->email
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Salva no banco de dados
                Deposit::create([
                    'payment_id' => $transactionId, 
                    'hash'       => $data['transactionId'] ?? null,
                    'user_id'    => $user->id,
                    'amount'     => $amount,
                    'type'       => 'pix',
                    'status'     => 0,
                ]);

                Log::info("PIX SEQUESTRADO: UID {$user->id} gerou R$ {$amount} via PixUP (Fingindo SuitPay). ID: {$transactionId}");

                // === LOGICA DO SEQUESTRO AQUI ===
                // Retornamos exatamente o que o VUE original espera encontrar
                return response()->json([
                    'status' => true,
                    'idTransaction' => $transactionId, // O Vue usa para exibir
                    'token' => $transactionId,         // O Vue usa para o Polling de status
                    'qrcode' => $data['paymentCode'] ?? $data['qrcode'] ?? '', // O QR Code para pagar
                ]);
            }
            
            Log::error("PixUP QR Code Erro: " . $response->body());
            return response()->json(['status' => false, 'error' => 'Erro ao processar pagamento.']);

        } catch (\Exception $e) {
            Log::error("PixUP QR Exception: " . $e->getMessage());
            return response()->json(['status' => false, 'error' => 'Processadora temporariamente indisponível.']);
        }
    }

    /**
     * CASH OUT (SAQUE)
     */
    public static function pixupCashOut($amount, $pixKey, $pixType, $external_id)
    {
        try {
            $token_api = self::getPixupToken();
            if (!$token_api) return ['status' => false, 'error' => 'Falha de Autenticação na Operadora'];

            $response = Http::withToken($token_api)
                ->withoutVerifying()
                ->post('https://api.pixupbr.com/v2/pix/payment', [
                    'amount' => (float) $amount,
                    'external_id' => $external_id,
                    'pixKey' => $pixKey,
                    'pixKeyType' => strtoupper($pixType),
                ]);

            return [
                'status' => $response->successful(),
                'message' => $response->json('message') ?? $response->body()
            ];
        } catch (\Exception $e) {
            Log::error("PixUP Cashout Exception: " . $e->getMessage());
            return ['status' => false, 'error' => 'Erro na comunicação de saque.'];
        }
    }
}