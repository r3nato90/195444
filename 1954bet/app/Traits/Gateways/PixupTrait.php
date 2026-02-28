<?php

namespace App\Traits\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Gateway;
use App\Models\Deposit;
use Illuminate\Support\Str;

trait PixupTrait
{
    /**
     * GERA O QR CODE PARA DEPÓSITO (Seguindo a documentação v2)
     */
    public static function requestQrcodePixup(Request $request)
    {
        $gateway = Gateway::first();
        $user = auth('api')->user();
        $amount = (float) $request->amount;
        $transactionId = Str::uuid()->toString();

        try {
            // 1. LOGIN VIA HEADER BASIC AUTH (Obrigatório conforme documentação)
            $credentials = base64_encode($gateway->pixup_client_id . ':' . $gateway->pixup_client_secret);
            
            $tokenReq = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Basic ' . $credentials,
                    'accept' => 'application/json'
                ])->post('https://api.pixupbr.com/v2/oauth/token');

            if (!$tokenReq->successful()) {
                \Log::error("PixUP Auth Erro: " . $tokenReq->body());
                return response()->json(['status' => false, 'error' => 'Falha na autenticação com o banco.']);
            }

            $token = $tokenReq->json('access_token');

            // 2. GERAÇÃO DO QR CODE
            $response = Http::withToken($token)->withoutVerifying()->post('https://api.pixupbr.com/v2/pix/qrcode', [
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
                
                Deposit::create([
                    'payment_id' => $data['transactionId'] ?? $transactionId,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'type' => 'pix',
                    'status' => 0,
                ]);

                return response()->json([
                    'status' => true,
                    'idTransaction' => $data['transactionId'] ?? $transactionId,
                    'qrcode' => $data['qrcode'] ?? '', // Conforme retorno da documentação [cite: 42]
                ]);
            }
            
            return response()->json(['status' => false, 'error' => 'Erro ao gerar o PIX na operadora.']);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error' => 'Conexão interrompida.']);
        }
    }

    public static function pixupCashOut($amount, $pixKey, $pixType, $external_id)
    {
        $gateway = Gateway::first();
        try {
            $credentials = base64_encode($gateway->pixup_client_id . ':' . $gateway->pixup_client_secret);
            $tokenReq = Http::withoutVerifying()->withHeaders(['Authorization' => 'Basic ' . $credentials])->post('https://api.pixupbr.com/v2/oauth/token');
            $token = $tokenReq->json('access_token');

            $response = Http::withToken($token)->withoutVerifying()->post('https://api.pixupbr.com/v2/pix/payment', [
                'amount' => (float) $amount,
                'external_id' => $external_id,
                'pixKey' => $pixKey,
                'pixKeyType' => strtoupper($pixType),
            ]);
            return ['status' => $response->successful()];
        } catch (\Exception $e) { return ['status' => false]; }
    }
}