<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Filament\Notifications\Notification;
use App\Traits\Gateways\PixupTrait;

class WalletController extends Controller
{
    use PixupTrait;

    /**
     * Retorna a carteira ativa do usuário
     * Resolve o erro: Method WalletController::index does not exist
     */
    public function index()
    {
        $wallet = Wallet::where('user_id', auth('api')->id())
            ->where('active', 1)
            ->first();

        return response()->json([
            'wallet' => $wallet
        ], 200);
    }

    /**
     * Verifica status da transação para o spinner do front-end
     */
    public function checkTransactionStatusByToken(Request $request)
    {
        $user = auth('api')->user();
        
        $lastDeposit = Deposit::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastDeposit) {
            return response()->json(['status' => 0, 'message' => 'Nenhuma transação'], 200);
        }

        return response()->json([
            'status' => $lastDeposit->status,
            'idTransaction' => $lastDeposit->payment_id
        ], 200);
    }

    /**
     * Processar saque - Chamado pelo botão "Aprovar" no Admin
     */
    public function withdrawalFromModal($id, Request $request = null)
    {
        $setting = Setting::first();
        $withdrawal = Withdrawal::find($id);

        if (!$withdrawal) {
            return back()->with('error', 'Saque não encontrado');
        }

        // Se o gateway padrão for PixUP, processa o Cash Out
        if ($setting->default_gateway === 'pixup') {
            $res = self::pixupCashOut($withdrawal->amount, $withdrawal->pix_key, $withdrawal->pix_type, $withdrawal->id);
            
            if (isset($res['status']) && $res['status'] === true) {
                $withdrawal->update(['status' => 1]);
                Notification::make()->title('Sucesso')->body('Saque pago via PixUP!')->success()->send();
                return back();
            }
        }

        Notification::make()->title('Erro')->body('Falha ao processar saque automático. Verifique o saldo PixUP.')->danger()->send();
        return back();
    }

    /**
     * Solicitação de Saque pelo Usuário (Saque Automático PixUP)
     */
    public function requestWithdrawal(Request $request)
    {
        $setting = Setting::first();
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:' . $setting->min_withdrawal],
            'pix_key' => 'required',
            'pix_type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if (floatval($request->amount) > floatval($user->wallet->balance_withdrawal)) {
            return response()->json(['error' => 'Saldo insuficiente'], 400);
        }

        // 1. Criar o registro pendente
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'pix_key' => $request->pix_key,
            'pix_type' => $request->pix_type,
            'status' => 0,
        ]);

        if ($withdrawal) {
            $user->wallet->decrement('balance_withdrawal', floatval($request->amount));

            // --- DISPARO AUTOMÁTICO PIXUP ---
            if ($setting->default_gateway === 'pixup') {
                $res = self::pixupCashOut($withdrawal->amount, $withdrawal->pix_key, $withdrawal->pix_type, $withdrawal->id);
                
                if (isset($res['status']) && $res['status'] === true) {
                    $withdrawal->update(['status' => 1]);
                    return response()->json([
                        'status' => true,
                        'message' => 'Saque realizado com sucesso e pago via PixUP!'
                    ], 200);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Solicitação enviada. Aguardando aprovação manual.'
            ], 200);
        }

        return response()->json(['error' => 'Erro ao processar saque'], 400);
    }

    public function myWallet()
    {
        $wallets = Wallet::whereUserId(auth('api')->id())->get();
        return response()->json(['wallets' => $wallets], 200);
    }

    public function setWalletActive($id)
    {
        $checkWallet = Wallet::whereUserId(auth('api')->id())->where('active', 1)->first();
        if ($checkWallet) $checkWallet->update(['active' => 0]);

        $wallet = Wallet::find($id);
        if ($wallet) {
            $wallet->update(['active' => 1]);
            return response()->json(['wallet' => $wallet], 200);
        }
    }
}