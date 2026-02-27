<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Notifications\NewWithdrawalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $wallet = Wallet::whereUserId(auth('api')->id())->where('active', 1)->first();
        return response()->json(['wallet' => $wallet], 200);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function myWallet()
    {
        $wallets = Wallet::whereUserId(auth('api')->id())->get();
        return response()->json(['wallets' => $wallets], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function setWalletActive($id)
    {
        $checkWallet = Wallet::whereUserId(auth('api')->id())->where('active', 1)->first();
        if (!empty($checkWallet)) {
            $checkWallet->update(['active' => 0]);
        }

        $wallet = Wallet::find($id);
        if (!empty($wallet)) {
            $wallet->update(['active' => 1]);
            return response()->json(['wallet' => $wallet], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestWithdrawal(Request $request)
    {
        try {
            $setting = Setting::first();

            if (auth('api')->check()) {
                $user = auth('api')->user();

                if ($user->utilizou_bonus_cadastro == 1) {
                    if (isset($setting->disable_deposit_min) && $setting->disable_deposit_min == 1) {
                        $depositSum = DB::table('deposits')
                            ->where('user_id', $user->id)
                            ->where('status', 1)
                            ->sum('amount');

                        if ($depositSum < $setting->deposit_min_saque) {
                            $remainingAmount = $setting->deposit_min_saque - $depositSum;
                            return response()->json(['error' => 'Você precisa depositar mais R$ ' . number_format($remainingAmount, 2, ',', '.') . ' para poder sacar.'], 400);
                        }
                    }
                }

                $rules = [
                    'amount' => ['required', 'numeric', 'min:' . ($setting->min_withdrawal ?? 0), 'max:' . ($setting->max_withdrawal ?? 999999)],
                    'type' => 'required',
                ];

                if ($request->type === 'pix') {
                    $rules['pix_type'] = 'required';
                    switch ($request->pix_type) {
                        case 'document':
                            $rules['pix_key'] = 'required'; // Removida a validação estrita 'cpf_ou_cnpj' para evitar travamentos se a biblioteca não estiver instalada
                            break;
                        case 'email':
                            $rules['pix_key'] = 'required|email';
                            break;
                        default:
                            $rules['pix_key'] = 'required';
                            break;
                    }
                } elseif ($request->type === 'bank') {
                    $rules['bank_info'] = 'required';
                }

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return response()->json($validator->errors(), 400);
                }

                if ($request->amount < 0) {
                    return response()->json(['error' => 'Você tem que solicitar um valor válido.'], 400);
                }

                if (isset($setting->max_withdrawal) && $request->amount > $setting->max_withdrawal) {
                    return response()->json(['error' => 'Você excedeu o limite máximo permitido de: R$ ' . $setting->max_withdrawal], 400);
                }

                if ($request->accept_terms == true || $request->accept_terms == 'true' || $request->accept_terms == 1) {
                    
                    $wallet = Wallet::where('user_id', $user->id)->first();
                    
                    if (floatval($request->amount) > floatval($wallet->balance_withdrawal)) {
                        return response()->json(['error' => 'Você não tem saldo de saque suficiente.'], 400);
                    }

                    $data = [
                        'user_id' => $user->id,
                        'amount' => \Helper::amountPrepare($request->amount),
                        'type' => $request->type,
                        'currency' => $request->currency ?? 'BRL',
                        'symbol' => $request->symbol ?? 'R$',
                        'status' => 0,
                    ];

                    if ($request->type === 'pix') {
                        $data['pix_key'] = $request->pix_key;
                        $data['pix_type'] = $request->pix_type;
                    } elseif ($request->type === 'bank') {
                        $data['bank_info'] = $request->bank_info;
                    }

                    $withdrawal = Withdrawal::create($data);

                    if ($withdrawal) {
                        $wallet->decrement('balance_withdrawal', floatval($request->amount));

                        if ($user->utilizou_bonus_cadastro == 1) {
                            $user->update(['utilizou_bonus_cadastro' => 0]);
                        }

                        $admins = User::where('role_id', 0)->get();
                        foreach ($admins as $admin) {
                            $admin->notify(new NewWithdrawalNotification($user->name, $request->amount));
                        }

                        return response()->json([
                            'status' => true,
                            'message' => 'Saque realizado com sucesso',
                        ], 200);
                    }
                }

                return response()->json(['error' => 'Você precisa aceitar os termos'], 400);
            }

            return response()->json(['error' => 'Erro ao realizar o saque. Usuário não autenticado.'], 401);
            
        } catch (\Exception $e) {
            Log::error('Erro ao solicitar saque: ' . $e->getMessage());
            return response()->json(['error' => 'Erro interno ao processar sua solicitação.'], 500);
        }
    }

    /**
     * BLINDADO: Evita erro 500 se as colunas estiverem nulas
     */
    public function updateBonusIfNeeded()
    {
        $userId = auth('api')->id();
        $wallet = Wallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return response()->json(['error' => 'Carteira não encontrada'], 404);
        }

        $messages = [];

        if (isset($wallet->balance_bonus) && floatval($wallet->balance_bonus) <= 0) {
            $wallet->balance_bonus_rollover = 0;
            $wallet->save();
            $messages[] = 'Bônus zerado e rollover atualizado.';
        }

        return response()->json(['messages' => $messages], 200);
    }

    /**
     * Transfere saldo para saque se necessário.
     */
    public function transferBalanceToWithdrawalIfNeeded()
    {
        $userId = auth('api')->id();
        $wallet = Wallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return response()->json(['error' => 'Carteira não encontrada'], 404);
        }

        $messages = [];

        if (isset($wallet->balance_deposit_rollover) && floatval($wallet->balance_deposit_rollover) <= 0 && floatval($wallet->balance) > 0) {
            $wallet->balance_withdrawal += $wallet->balance;
            $wallet->balance = 0; 
            $wallet->save();
            $messages[] = 'Saldo transferido para saque com sucesso!';
        }

        return response()->json(['messages' => $messages], 200);
    }

    /**
     * Transfere bônus para saque se necessário.
     */
    public function transferBonusToWithdrawalIfNeeded()
    {
        $userId = auth('api')->id();
        $wallet = Wallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return response()->json(['error' => 'Carteira não encontrada'], 404);
        }

        $messages = [];

        if (isset($wallet->balance_bonus_rollover) && floatval($wallet->balance_bonus_rollover) <= 0 && floatval($wallet->balance_bonus) > 0) {
            $wallet->balance_withdrawal += $wallet->balance_bonus;
            $wallet->balance_bonus = 0; 
            $wallet->save();
            $messages[] = 'Saldo do bônus transferido para saque com sucesso!';
        }

        return response()->json(['messages' => $messages], 200);
    }

    /**
     * Verifica e atualiza bônus, saldo e transferências.
     */
    public function checkAndUpdateBonus()
    {
        $userId = auth('api')->id();
        $wallet = Wallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return response()->json(['error' => 'Carteira não encontrada'], 404);
        }

        $messages = [];

        if (isset($wallet->balance_bonus) && floatval($wallet->balance_bonus) <= 0) {
            $wallet->balance_bonus_rollover = 0;
            $wallet->save();
            $messages[] = 'Bônus zerado e rollover atualizado.';
        }

        if (isset($wallet->balance_deposit_rollover) && floatval($wallet->balance_deposit_rollover) <= 0 && floatval($wallet->balance) > 0) {
            $wallet->balance_withdrawal += floatval($wallet->balance);
            $wallet->balance = 0; 
            $wallet->save();
            $messages[] = 'Saldo transferido para saque com sucesso!';
        }

        if (isset($wallet->balance_bonus_rollover) && floatval($wallet->balance_bonus_rollover) <= 0 && floatval($wallet->balance_bonus) > 0) {
            $wallet->balance_withdrawal += floatval($wallet->balance_bonus);
            $wallet->balance_bonus = 0; 
            $wallet->save();
            $messages[] = 'Saldo do bônus transferido para saque com sucesso!';
        }

        if (empty($messages)) {
            return response()->json(['message' => 'Nenhuma atualização necessária'], 200);
        }

        return response()->json(['messages' => $messages], 200);
    }
}