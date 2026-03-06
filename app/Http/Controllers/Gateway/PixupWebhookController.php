<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PixupWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log para auditoria em storage/logs/laravel.log ou pixup.log
        Log::channel('pixup')->info('PIXUP WEBHOOK RECEBIDO:', $request->all());

        $payload = $request->input('requestBody');

        if (!$payload) {
            Log::channel('pixup')->error('PIXUP WEBHOOK: Payload requestBody não encontrado.');
            return response()->json(['error' => 'Payload inválido'], 400);
        }

        $type = $payload['transactionType'] ?? null;
        $externalId = $payload['external_id'] ?? null; 
        $pixupId = $payload['transactionId'] ?? null;  
        $status = strtoupper($payload['status'] ?? '');

        // === PROCESSA DEPÓSITO (Crédito para o Usuário) ===
        if (($type === 'PAYMENT' || $type === 'RECEIVEPIX') && in_array($status, ['APPROVED', 'PAID', 'SUCCESS'])) {
            
            return DB::transaction(function () use ($externalId, $pixupId, $payload) {
                
                // Busca o depósito pendente
                $deposit = Deposit::where(function($query) use ($externalId, $pixupId) {
                        $query->where('payment_id', $externalId)
                              ->orWhere('payment_id', $pixupId);
                    })
                    ->where('status', 0) 
                    ->lockForUpdate() 
                    ->first();
                
                if ($deposit) {
                    // 1. Marca como Pago
                    $deposit->update([
                        'status' => 1,
                        'proof' => $pixupId
                    ]);

                    // 2. Credita na Carteira
                    $wallet = Wallet::where('user_id', $deposit->user_id)->first();
                    if ($wallet) {
                        $wallet->increment('balance', $deposit->amount);
                        
                        // 3. Marca primeiro depósito e aciona bônus se necessário
                        $user = User::find($deposit->user_id);
                        if ($user && $user->is_first_deposit == 0) {
                            $user->update(['is_first_deposit' => 1]);
                            // Se o seu sistema tiver Helper de Bônus, chame aqui:
                            // \App\Helpers\BonusHelper::checkAndApply($user, $deposit->amount);
                        }

                        Log::channel('pixup')->info("PIXUP SUCESSO: R$ {$deposit->amount} creditados para UID {$deposit->user_id}.");
                        return response()->json(['success' => true]);
                    }
                }
                
                return response()->json(['message' => 'Já processado'], 200);
            });
        } 
        
        // === PROCESSA SAQUE (Baixa no Sistema) ===
        elseif ($type === 'TRANSFER' && in_array($status, ['APPROVED', 'PAID', 'SUCCESS'])) {
            
            $withdrawal = Withdrawal::where(function($query) use ($externalId, $pixupId) {
                    $query->where('payment_id', $externalId) // Alinhado com o campo do seu banco
                          ->orWhere('payment_id', $pixupId);
                })
                ->where('status', 0) // Pendente
                ->first();
            
            if ($withdrawal) {
                // Alinhado com seu WalletController: 1 = Pago
                $withdrawal->update(['status' => 1]); 
                Log::channel('pixup')->info("PIXUP SAQUE: Confirmado ID {$pixupId}");
                return response()->json(['success' => true]);
            }
        }

        return response()->json(['message' => 'OK'], 200);
    }
}