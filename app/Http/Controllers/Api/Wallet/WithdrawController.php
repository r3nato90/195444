<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Traits\Gateways\PixupTrait;

class WithdrawController extends Controller
{
    use PixupTrait;

    public function index(Request $request)
    {
        $userId = auth('api')->id();
        
        // Melhoria: traz os saques mais recentes primeiro
        $query = Withdrawal::where('user_id', $userId)->orderBy('id', 'desc');
        
        if ($request->has('filter')) {
            $filter = $request->input('filter');
            switch ($filter) {
                case 'today': 
                    $query->whereDate('created_at', Carbon::today()); 
                    break;
                case 'week': 
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]); 
                    break;
                case 'month': 
                    $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]); 
                    break;
                case 'year': 
                    $query->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]); 
                    break;
            }
        }
        
        return response()->json(['status' => true, 'withdraws' => $query->paginate()], 200);
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

        // SEGURANÇA MÁXIMA: Transação no Banco de Dados com bloqueio de linha (lockForUpdate)
        // Isso impede que um usuário envie duas requisições simultâneas e duplique o saque.
        return DB::transaction(function () use ($request, $user) {
            
            // Bloqueia a leitura da carteira para outras requisições até esta terminar
            $wallet = $user->wallet()->lockForUpdate()->first();
            $amount = (float) $request->amount;

            if (!$wallet || $wallet->balance < $amount) {
                return response()->json(['status' => false, 'error' => 'Saldo insuficiente.'], 400);
            }

            $setting = Setting::first();
            $transactionId = Str::uuid()->toString();
            $gateway = $setting ? strtolower($setting->default_gateway) : '';

            // Forçamos a verificação de 'suitpay' que no backend chamará o PixUP
            if (in_array($gateway, ['suitpay', 'pixup', 'pixupbr'])) {
                
                // Dispara o pedido de Cashout para a API da Pixup
                $response = self::pixupCashOut($amount, $request->pix_key, $request->pix_type, $transactionId);

                if (isset($response['status']) && $response['status']) {
                    
                    // Desconta o saldo do usuário com segurança
                    $wallet->decrement('balance', $amount);
                    
                    // Registra o saque como 'pending'. O Webhook confirmará e passará para 'approved'
                    Withdrawal::create([
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'pix_key' => $request->pix_key,
                        'pix_type' => $request->pix_type,
                        'status' => 'pending', 
                        'transaction_id' => $transactionId,
                        'type' => 'pix' // Mantém o padrão do banco
                    ]);
                    
                    return response()->json(['status' => true, 'msg' => 'Saque solicitado com sucesso! Em instantes estará na sua conta.'], 200);
                }
                
                Log::channel('pixup')->error("PixUP Erro Saque: " . json_encode($response));
                return response()->json(['status' => false, 'error' => 'A operadora recusou a transação ou os dados PIX são inválidos.'], 400);
            }
            
            return response()->json(['status' => false, 'error' => 'Gateway de saque inativo.'], 400);
        });
    }
}