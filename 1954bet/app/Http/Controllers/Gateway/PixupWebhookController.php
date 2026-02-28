<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;

class PixupWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->input('requestBody');

        if (!$payload) {
            return response()->json(['error' => 'Sem payload'], 400);
        }

        $type = $payload['transactionType'] ?? null;
        $transactionId = $payload['external_id'] ?? $payload['transactionId'] ?? null;
        $status = $payload['status'] ?? 'APPROVED';

        if ($type === 'PAYMENT' && $status === 'APPROVED') {
            // === PROCESSA DEPÓSITO ===
            $deposit = Deposit::where('payment_id', $transactionId)->where('status', 0)->first();
            
            if ($deposit) {
                // Atualiza o depósito para pago
                $deposit->update(['status' => 1]);

                // Credita na carteira do usuário
                $wallet = Wallet::where('user_id', $deposit->user_id)->first();
                if ($wallet) {
                    $wallet->increment('balance', $deposit->amount);
                }
                return response()->json(['message' => 'Depósito aprovado com sucesso!']);
            }
        } 
        elseif ($type === 'TRANSFER' && $status === 'APPROVED') {
            // === PROCESSA SAQUE ===
            $withdrawal = Withdrawal::where('transaction_id', $transactionId)->where('status', 'pending')->first();
            
            if ($withdrawal) {
                $withdrawal->update(['status' => 'approved']); // Confirma o saque
                return response()->json(['message' => 'Saque aprovado com sucesso!']);
            }
        }

        return response()->json(['message' => 'Recebido'], 200);
    }
}