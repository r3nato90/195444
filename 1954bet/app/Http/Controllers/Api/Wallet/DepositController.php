<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Traits\Gateways\SuitpayTrait;
use App\Http\Controllers\Gateway\WishPagController;
use App\Http\Controllers\Gateway\VersellController;
use App\Http\Controllers\Gateway\PixupController; // <-- CAMINHO CORRIGIDO!
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DepositController extends Controller
{
    use SuitpayTrait;

    /**
     * Retorna o histórico de depósitos do usuário
     */
    public function index()
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(['status' => false, 'error' => 'Não autorizado'], 401);
            }

            $deposits = Deposit::where('user_id', $user->id)
                               ->orderBy('id', 'desc')
                               ->paginate(15);

            return response()->json(['status' => true, 'deposits' => $deposits], 200);

        } catch (\Exception $e) {
            Log::error("Erro em DepositController@index: " . $e->getMessage());
            return response()->json(['status' => false, 'error' => 'Erro ao buscar histórico'], 500);
        }
    }

    public function submitPayment(Request $request)
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(['status' => false, 'error' => 'Sessão expirada. Faça login novamente.'], 200);
            }

            $userId = $user->id;

            $gatewayConfig = Gateway::first();
            if (!$gatewayConfig || empty($gatewayConfig->default_gateway)) {
                return response()->json(['status' => false, 'error' => 'Nenhum gateway configurado no painel admin.'], 200);
            }

            $gatewaySelected = strtolower($gatewayConfig->default_gateway);
            
            $amount = floatval(str_replace(',', '.', $request->amount));
            if ($amount <= 0) {
                return response()->json(['status' => false, 'error' => 'Valor do depósito inválido.'], 200);
            }

            Log::info("Processando depósito. Gateway: " . $gatewaySelected . " | Valor: " . $amount);

            switch ($gatewaySelected) {
                case 'bspay': // WishPag
                case 'wishpag':
                    return WishPagController::generatePix($amount, $userId);

                case 'digitopay': // Pixup
                case 'pixup':
                    $pixup = new PixupController();
                    return $pixup->generateQRCode($request);

                case 'ezzebank': // Versell Pay
                case 'versell':
                    return VersellController::generatePix($amount, $userId);

                case 'suitpay':
                    config(['suitpay.uri' => rtrim($gatewayConfig->suitpay_uri ?? '', '/') . '/']);
                    config(['suitpay.client_id' => $gatewayConfig->suitpay_cliente_id ?? '']);
                    config(['suitpay.client_secret' => $gatewayConfig->suitpay_cliente_secret ?? '']);
                    return self::requestQrcode($request);

                default:
                    return response()->json(['status' => false, 'error' => 'Gateway selecionado no admin é inválido.'], 200);
            }
        } catch (\Throwable $e) {
            Log::error("Erro Fatal DepositController: " . $e->getMessage() . " Linha: " . $e->getLine());
            return response()->json([
                'status' => false, 
                'error' => 'Erro interno ao processar depósito. Tente novamente mais tarde.'
            ], 200);
        }
    }

    public function consultStatus($idTransaction)
    {
        try {
            $deposit = Deposit::where('payment_id', $idTransaction)
                              ->orWhere('hash', $idTransaction)
                              ->first();
                              
            if (!$deposit) {
                return response()->json(['status' => 'not_found'], 200);
            }

            return response()->json([
                'status' => $deposit->status == 1 ? 'paid' : 'pending',
                'amount' => $deposit->amount
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'error' => 'Erro ao consultar status.'], 200);
        }
    }
}