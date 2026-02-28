<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Traits\Gateways\PixupTrait;

class WithdrawController extends Controller
{
    use PixupTrait;

    public function index(Request $request)
    {
        $userId = auth('api')->id();
        $query = Withdrawal::whereUserId($userId);
        if ($request->has('filter')) {
            $filter = $request->input('filter');
            switch ($filter) {
                case 'today': $query->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()->subSecond()]); break;
                case 'week': $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]); break;
                case 'month': $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]); break;
                case 'year': $query->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]); break;
            }
        }
        return response()->json(['withdraws' => $query->paginate()], 200);
    }

    public function verifyPassword(Request $request)
    {
        $request->validate(['password' => 'required|string|min:6|max:6']);
        $user = auth('api')->user();
        return response()->json(['valid' => Hash::check($request->password, $user->withdrawal_password)], 200);
    }

    public function makeWithdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
            'pix_key' => 'required|string',
            'pix_type' => 'required|string',
        ]);

        $user = auth('api')->user();
        $wallet = $user->wallet;
        $amount = (float) $request->amount;

        if ($wallet->balance < $amount) {
            return response()->json(['status' => false, 'error' => 'Saldo insuficiente.'], 400);
        }

        $setting = Setting::first();
        $transactionId = Str::uuid()->toString();

        // Forçamos a verificação de 'suitpay' que no backend chamará o PixUP
        if ($setting && ($setting->default_gateway === 'suitpay' || $setting->default_gateway === 'pixup')) {
            $response = self::pixupCashOut($amount, $request->pix_key, $request->pix_type, $transactionId);

            if ($response['status']) {
                $wallet->decrement('balance', $amount);
                Withdrawal::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'pix_key' => $request->pix_key,
                    'pix_type' => $request->pix_type,
                    'status' => 'pending', 
                    'transaction_id' => $transactionId,
                ]);
                return response()->json(['status' => true, 'msg' => 'Saque solicitado com sucesso!'], 200);
            }
            return response()->json(['status' => false, 'error' => 'A PixUP recusou o processamento.'], 400);
        }
        return response()->json(['status' => false, 'error' => 'Gateway de saque não configurado.'], 400);
    }
}