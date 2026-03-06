<?php

namespace App\Traits\Gateways;

use Illuminate\Support\Facades\Http;
use App\Models\Gateway;
use App\Models\Deposit;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

trait WishpagTrait
{
    /**
     * Gerar QR Code WishPag (Integração via Sequestro)
     * * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function requestQrcodeWishpag($request)
    {
        try {
            // 1. Busca as credenciais no banco de dados
            $gateway = Gateway::first();
            $user = auth('api')->user();
            
            if (!$gateway || !$gateway->wishpag_secret_key || !$gateway->wishpag_company_id) {
                return response()->json(['status' => false, 'error' => 'Configurações da WishPag ausentes no painel.']);
            }

            // 2. Prepara os dados da transação
            $transactionId = Str::uuid()->toString();
            $amount = (float) $request->amount;

            // 3. Chamada para a API WishPag usando Basic Auth
            // Username: Secret Key | Password: Company ID
            $response = Http::withBasicAuth($gateway->wishpag_secret_key, $gateway->wishpag_company_id)
                ->withoutVerifying()
                ->post('https://api.wishpag.com/v1/pix/cash-in', [
                    'amount' => $amount,
                    'external_id' => $transactionId,
                    'callback_url' => url('/api/gateways/wishpag/webhook'),
                    'payer' => [
                        'name' => $user->name,
                        'document' => preg_replace('/[^0-9]/', '', $user->cpf ?? '00000000000'),
                        'email' => $user->email
                    ]
                ]);

            // 4. Processa a resposta
            if ($response->successful()) {
                $data = $response->json();
                
                // Salva o registro no banco de dados para o webhook localizar depois
                Deposit::create([
                    'payment_id' => $transactionId,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'type' => 'pix',
                    'status' => 0
                ]);

                Log::info("WISHPAG SEQUESTRO: PIX gerado com sucesso para o UID {$user->id}. ID: {$transactionId}");

                // Retorno formatado para o seu Vue original (SuitPay Style)
                return response()->json([
                    'status' => true,
                    'idTransaction' => $transactionId,
                    'token' => $transactionId, // O seu Vue busca este campo para o polling
                    'qrcode' => $data['qrcode'] ?? $data['payment_code'] ?? ''
                ]);
            }

            // Log de erro caso a API responda algo inesperado
            Log::error("WishPag API Erro: " . $response->body());
            return response()->json(['status' => false, 'error' => 'A operadora WishPag recusou a solicitação.']);

        } catch (\Exception $e) {
            Log::error("WishPag Exception: " . $e->getMessage());
            return response()->json(['status' => false, 'error' => 'Falha técnica ao gerar PIX via WishPag.']);
        }
    }
}