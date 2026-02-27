<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WishPagController extends Controller
{
    private static function getCredentials()
    {
        $gateway = Gateway::first();
        return [
            'secret_key' => $gateway ? $gateway->bspay_cliente_id : '',
            'company_id' => $gateway ? $gateway->bspay_cliente_secret : '',
            'base_url'   => rtrim($gateway->bspay_uri ?? 'https://api.wishpag.com/v1', '/')
        ];
    }

    public static function generatePix($amount, $userId)
    {
        try {
            $creds = self::getCredentials();
            
            if (empty($creds['secret_key']) || empty($creds['company_id'])) {
                // Retornando 200 com status false para gerar o popup no site
                return response()->json(['status' => false, 'error' => 'Credenciais WishPag ausentes no Admin.'], 200);
            }

            $auth = base64_encode($creds['secret_key'] . ':' . $creds['company_id']);
            $externalId = (string) Str::uuid();

            $response = Http::withoutVerifying()
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->withOptions(['curl' => [CURLOPT_SSLVERSION => 0]])
                ->withHeaders([
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->post($creds['base_url'] . '/payments', [
                    'amount' => (int) ($amount * 100),
                    'payment_method' => 'pix',
                    'external_id' => $externalId,
                    'postback_url' => url('/api/webhooks/wishpag/cashin'),
                ]);

            if ($response->successful()) {
                $responseData = $response->json();

                Transaction::create([
                    'user_id' => $userId,
                    'payment_id' => $responseData['id'] ?? $externalId,
                    'amount' => $amount,
                    'status' => 'pending',
                ]);

                return response()->json([
                    'status' => true,
                    'idTransaction' => $responseData['id'] ?? $externalId,
                    'qrcode' => $responseData['qr_code'] ?? '', 
                    'copia_e_cola' => $responseData['pix_code'] ?? $responseData['emv'] ?? ''
                ]);
            }

            Log::error('Erro WishPag API HTTP ' . $response->status() . ': ' . $response->body());
            return response()->json(['status' => false, 'error' => 'Erro ao comunicar com a WishPag. Verifique o Painel.'], 200);
            
        } catch (\Throwable $e) {
            Log::error('Erro Fatal no WishPagController: ' . $e->getMessage());
            return response()->json(['status' => false, 'error' => 'Erro interno ao gerar PIX na WishPag.'], 200);
        }
    }

    public function webhookCashIn(Request $request)
    {
        try {
            $data = $request->all();
            
            if (isset($data['status']) && in_array($data['status'], ['paid', 'completed', 'approved'])) {
                $transaction = Transaction::where('payment_id', $data['id'])
                    ->orWhere('payment_id', $data['external_id'] ?? '')
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
            Log::error('Erro Webhook WishPag In: ' . $e->getMessage());
            return response()->json(['status' => 'OK'], 200);
        }
    }

    public static function pixCashOut($withdrawal)
    {
        try {
            $creds = self::getCredentials();
            $auth = base64_encode($creds['secret_key'] . ':' . $creds['company_id']);

            $response = Http::withoutVerifying()
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->withOptions(['curl' => [CURLOPT_SSLVERSION => 0]])
                ->withHeaders([
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->post($creds['base_url'] . '/withdrawals', [
                    'amount' => (int) ($withdrawal->amount * 100),
                    'pix_key' => $withdrawal->pix_key,
                    'pix_type' => self::formatPixType($withdrawal->pix_type),
                    'external_id' => (string) $withdrawal->id,
                ]);

            return $response->json();
        } catch (\Throwable $e) {
            return ['status' => false, 'error' => 'Falha interna ao processar saque'];
        }
    }

    public function webhookCashOut(Request $request)
    {
        try {
            $data = $request->all();
            $externalId = $data['external_id'] ?? ($data['withdrawal']['id'] ?? null);
            $status = $data['status'] ?? '';

            if ($externalId) {
                $withdrawal = Withdrawal::where('id', $externalId)->first();

                if ($withdrawal && $withdrawal->status === 'pending') {
                    if (in_array($status, ['completed', 'approved', 'paid'])) {
                        $withdrawal->update(['status' => 'completed']);
                    }
                    elseif (in_array($status, ['failed', 'canceled', 'rejected', 'returned'])) {
                        $withdrawal->update(['status' => 'failed']);
                        $wallet = Wallet::where('user_id', $withdrawal->user_id)->first();
                        if ($wallet) {
                            $wallet->increment('balance_withdrawal', $withdrawal->amount);
                        }
                    }
                }
            }
            return response()->json(['status' => 'OK'], 200);
        } catch (\Exception $e) {
            Log::error('Erro Webhook WishPag Out: ' . $e->getMessage());
            return response()->json(['status' => 'OK'], 200);
        }
    }

    private static function formatPixType($type)
    {
        $type = strtolower($type);
        $map = [
            'cpf' => 'cpf',
            'cnpj' => 'cnpj',
            'email' => 'email',
            'phone' => 'phone',
            'random' => 'evp',
        ];
        return $map[$type] ?? 'evp'; 
    }
}