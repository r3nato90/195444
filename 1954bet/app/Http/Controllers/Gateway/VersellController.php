<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VersellController extends Controller
{
    /**
     * Obtém as credenciais mapeadas da Versell Pay (usando colunas ezzebank)
     */
    private static function getCredentials()
    {
        $gateway = Gateway::first();
        return [
            'vspi' => $gateway->ezzebank_cliente_id,
            'vsps' => $gateway->ezzebank_cliente_secret,
            'base_url' => $gateway->ezzebank_uri ?? 'https://api.versellpay.com'
        ];
    }

    /**
     * Cash-in: Gerar Pagamento PIX
     */
    public static function generatePix($amount, $userId)
    {
        $creds = self::getCredentials();
        
        if (empty($creds['vspi']) || empty($creds['vsps'])) {
            return ['error' => 'Configurações da Versell Pay ausentes.'];
        }

        // Endpoint padrão para geração de PIX (ajuste se a doc completa indicar um path diferente)
        $endpoint = $creds['base_url'] . '/api/v1/gateway/generatePix';

        $response = Http::withHeaders([
            'vspi' => $creds['vspi'],
            'vsps' => $creds['vsps'],
            'Content-Type' => 'application/json'
        ])->post($endpoint, [
            'amount' => (float) $amount,
            'webhookUrl' => url('/api/webhooks/versell/cashin'), // Envio da URL de postback
            // ... outros dados do pagador conforme a doc completa da Versell
        ]);

        return $response->json();
    }

    /**
     * Webhook para Depósitos (Cash-in)
     */
    public function webhookCashIn(Request $request)
    {
        Log::info('Versell Webhook CashIn:', $request->all());

        try {
            $data = $request->all();
            
            // Verifica o status de sucesso da Versell (ex: 'PAID' ou 'APPROVED')
            if (isset($data['status']) && strtoupper($data['status']) === 'PAID') {
                $transaction = Transaction::where('payment_id', $data['idTransaction'] ?? $data['id'])
                    ->where('status', 'pending')
                    ->first();

                if ($transaction) {
                    $wallet = Wallet::where('user_id', $transaction->user_id)->first();
                    if ($wallet) {
                        $wallet->increment('balance', $transaction->amount);
                        $transaction->update(['status' => 'completed', 'approved_at' => now()]);
                    }
                }
            }

            return response()->json(['status' => 'OK'], 200);
        } catch (\Exception $e) {
            Log::error('Erro Webhook Versell In: ' . $e->getMessage());
            return response()->json(['status' => 'OK'], 200);
        }
    }

 /**
     * Enviar PIX (Cash-Out)
     */
    public static function pixCashOut($withdrawal)
    {
        $creds = self::getCredentials();

        $response = Http::withHeaders([
            'vspi' => $creds['vspi'],
            'vsps' => $creds['vsps'],
            'Content-Type' => 'application/json'
        ])->post($creds['base_url'] . '/api/v1/gateway/transferPix', [
            'amount' => (float) $withdrawal->amount,
            'key' => $withdrawal->pix_key,
            'typeKey' => $withdrawal->pix_type,
            'requestNumber' => (string) $withdrawal->id,
        ]);

        return $response->json();
    }   
    
    
}