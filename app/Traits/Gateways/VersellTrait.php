<?php
namespace App\Traits\Gateways;

use Illuminate\Support\Facades\Http;
use App\Models\Gateway;
use App\Models\Deposit;
use Illuminate\Support\Str;

trait VersellTrait {
    public static function requestQrcodeVersell($request) {
        $gateway = Gateway::first();
        $user = auth('api')->user();
        $transactionId = Str::uuid()->toString();
        $amount = (float) $request->amount;

        $response = Http::withHeaders([
            'vspi' => $gateway->versell_client_id,
            'vsps' => $gateway->versell_client_secret,
            'Content-Type' => 'application/json'
        ])->post('https://api.versellpay.com/api/v1/gateway/generatePix', [
            'amount' => $amount,
            'external_id' => $transactionId,
            'postbackUrl' => url('/api/gateways/versell/webhook'),
            'payerName' => $user->name,
            'payerDocument' => preg_replace('/[^0-9]/', '', $user->cpf)
        ]);

        if ($response->successful()) {
            $data = $response->json();
            Deposit::create(['payment_id' => $transactionId, 'user_id' => $user->id, 'amount' => $amount, 'type' => 'pix', 'status' => 0]);

            return response()->json([
                'status' => true,
                'idTransaction' => $transactionId,
                'token' => $transactionId,
                'qrcode' => $data['qrcode'] ?? $data['paymentCode']
            ]);
        }
        return response()->json(['status' => false, 'error' => 'Erro na Versell']);
    }
}