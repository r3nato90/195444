<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Gateway;
use App\Http\Controllers\Gateway\WishPagController;
use App\Http\Controllers\Gateway\PixupController;
use App\Http\Controllers\Gateway\VersellController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WithdrawController extends Controller
{
    /**
     * Retorna o histórico de saques do usuário
     */
    public function index()
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(['status' => false, 'error' => 'Não autorizado'], 401);
            }

            // Busca os saques realizados pelo usuário logado
            $withdrawals = Withdrawal::where('user_id', $user->id)
                               ->orderBy('id', 'desc')
                               ->paginate(15);

            return response()->json(['status' => true, 'withdrawals' => $withdrawals], 200);

        } catch (\Exception $e) {
            Log::error("Erro em WithdrawController@index: " . $e->getMessage());
            return response()->json(['status' => false, 'error' => 'Erro ao buscar histórico de saques'], 500);
        }
    }

    /**
     * Verifica a senha do usuário antes de permitir o saque
     */
    public function verifyPassword(Request $request)
    {
        $request->validate(['password' => 'required']);
        $user = auth('api')->user();

        if (Hash::check($request->password, $user->password)) {
            return response()->json(['status' => true], 200);
        }

        return response()->json(['status' => false, 'error' => 'Senha incorreta.'], 200);
    }

    /**
     * Solicitação e Processamento de Saque
     */
    public function requestWithdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:20', 
            'pix_key' => 'required|string',
            'pix_type' => 'required|string', // cpf, email, phone, random
        ]);

        $user = auth('api')->user();
        $amount = floatval($request->amount);

        // 1. Verifica Saldo
        if ($user->wallet->balance < $amount) {
            return response()->json(['error' => 'Saldo insuficiente.'], 400);
        }

        // 2. Desconta Saldo Preventivamente
        $user->wallet->decrement('balance', $amount);

        // 3. Registra o Saque no Banco
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'pix_key' => $request->pix_key,
            'pix_type' => $request->pix_type,
            'status' => 'pending',
        ]);

        // 4. Verifica Gateway Padrão
        $gateway = Gateway::first();
        if (!$gateway) {
            $user->wallet->increment('balance', $amount);
            $withdrawal->update(['status' => 'failed']);
            return response()->json(['error' => 'Configuração de gateway não encontrada.'], 500);
        }

        try {
            $res = null;
            // Dispara o PIX com base no gateway selecionado no Painel
            switch (strtolower($gateway->default_gateway)) {
                case 'suitpay':
                    // Se tiver SuitPayTrait integrado, pode chamar aqui
                    break;

                case 'bspay': 
                case 'wishpag':
                    $res = WishPagController::pixCashOut($withdrawal);
                    break;

                case 'digitopay': 
                case 'pixup':
                    $res = PixupController::pixCashOut($withdrawal);
                    break;

                case 'ezzebank': 
                case 'versell':
                    $res = VersellController::pixCashOut($withdrawal);
                    break;

                default:
                    throw new \Exception("Gateway [{$gateway->default_gateway}] não configurado ou suportado.");
            }

            return response()->json([
                'status' => true,
                'message' => 'Saque solicitado com sucesso!',
                'data' => $res ?? []
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao processar saque: ' . $e->getMessage());
            
            // Devolve o saldo e marca como falha em caso de erro crítico
            $user->wallet->increment('balance', $amount);
            $withdrawal->update(['status' => 'failed']);
            
            return response()->json(['error' => 'Erro ao processar saque no gateway. Tente novamente mais tarde.'], 500);
        }
    }
}