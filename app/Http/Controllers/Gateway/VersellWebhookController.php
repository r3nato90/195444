<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VersellWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log para auditoria
        Log::channel('versell')->info('VERSELL WEBHOOK:', $request->all());

        // A Versell geralmente envia dentro de um objeto transaction ou direto
        $externalId = $request->input('external_id') ?? $request->input('transaction.external_id');
        $status = strtoupper($request->input('status') ?? $request->input('transaction.status', ''));

        if (in_array($status, ['PAID', 'APPROVED', 'SUCCESS'])) {
            
            return DB::transaction(function () use ($externalId, $request) {
                $deposit = Deposit::where('payment_id', $externalId)
                    ->where('status', 0)
                    ->lockForUpdate()
                    ->first();

                if ($deposit) {
                    $deposit->update([
                        'status' => 1,
                        'proof' => $request->input('transactionId') // ID da Versell
                    ]);

                    $wallet = Wallet::where('user_id', $deposit->user_id)->first();
                    if ($wallet) {
                        $wallet->increment('balance', $deposit->amount);
                        
                        $user = User::find($deposit->user_id);
                        if ($user && $user->is_first_deposit == 0) {
                            $user->update(['is_first_deposit' => 1]);
                        }

                        Log::channel('versell')->info("SUCESSO: R$ {$deposit->amount} creditados ao UID {$deposit->user_id}");
                        return response()->json(['status' => 'success'], 200);
                    }
                }
                return response()->json(['status' => 'ignored'], 200);
            });
        }

        return response()->json(['status' => 'received'], 200);
    }
}