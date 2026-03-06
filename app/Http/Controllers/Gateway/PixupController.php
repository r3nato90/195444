<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PixupController extends Controller
{
    private static function getToken($config)
    {
        $id = $config->pixup_client_id;
        $secret = $config->pixup_client_secret;
        $credentials = base64_encode($id . ':' . $secret);

        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'Accept' => 'application/json'
            ])
            ->post('https://api.pixupbr.com/v2/oauth/token');

        if ($response->successful()) {
            $data = $response->json();
            return $data['access_token'] ?? null;
        }
        
        Log::error("PixUP Auth Erro: " . $response->body());
        return null;
    }

    public function generateQRCode(Request $request)
    {
        try {
            $config = Gateway::first();
            $token = self::getToken($config);
            
            if (!$token) {
                return response()->json(['status' => false, 'error' => 'Erro Auth Pixup'], 200);
            }

            $user = auth('api')->user();
            $amount = (float)$request->amount;
            $external_id = uniqid('pix_');

            $response = Http::withToken($token)
                ->withOptions(['verify' => false])
                ->post('https://api.pixupbr.com/v2/pix/qrcode', [
                    'amount' => $amount,
                    'external_id' => $external_id,
                    // URL CORRIGIDA PARA BATER COM O SEU VerifyCsrfToken e api.php
                    'postbackUrl' => url('/api/gateways/pixup/webhook'), 
                    'payer' => [
                        'name' => $user->name,
                        'document' => preg_replace('/[^0-9]/', '', $user->cpf ?? '00000000000'),
                        'email' => $user->email
                    ]
                ]);

            if ($response->successful()) {
                $pix = $response->json();
                
                // Criação do depósito aprimorada com o tipo e hash de backup
                Deposit::create([
                    'user_id' => $user->id, 
                    'amount' => $amount, 
                    'payment_id' => $external_id, 
                    'hash' => $pix['transactionId'] ?? null,
                    'type' => 'pix',
                    'status' => 0
                ]);
                
                return response()->json([
                    'status' => true, 
                    'qrcode' => $pix['qrcode'] ?? '',
                    'qrcodeText' => $pix['paymentCode'] ?? $pix['qrcodeText'] ?? '' // Retorno do PIX Copia e Cola
                ]);
            }
            
            Log::error("PixUP QR Code Erro: " . $response->body());
            return response()->json(['status' => false, 'error' => 'Erro API Pixup'], 200);
            
        } catch (\Exception $e) {
            Log::channel('pixup')->error("PixUP Exception: " . $e->getMessage());
            return response()->json(['status' => false, 'error' => $e->getMessage()], 200);
        }
    }
}