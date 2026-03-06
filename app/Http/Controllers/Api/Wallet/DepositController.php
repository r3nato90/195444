<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\Setting;
use App\Traits\Gateways\PixupTrait;
use App\Traits\Gateways\WishpagTrait;
use App\Traits\Gateways\VersellTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DepositController extends Controller
{
    use PixupTrait, WishpagTrait, VersellTrait;

    /**
     * Listar Depósitos (Resolve erro Method index does not exist)
     */
    public function index()
    {
        $deposits = Deposit::where('user_id', auth('api')->id())
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'deposits' => $deposits
        ]);
    }

    /**
     * Submeter Pagamento (Lógica do Sequestro Dinâmico)
     */
    public function submitPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'gateway' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()], 400);
        }

        $setting = Setting::first();
        $activeGateway = $setting->default_gateway; 

        Log::info("DEBUG SEQUESTRO: Gateway ativo no banco é [{$activeGateway}].");

        switch ($activeGateway) {
            case 'wishpag':
                return self::requestQrcodeWishpag($request);
            case 'versell':
                return self::requestQrcodeVersell($request);
            case 'pixup':
                return self::requestQrcodePixup($request);
            default:
                return response()->json(['status' => false, 'error' => 'Configuração de gateway inválida.'], 200);
        }
    }

    /**
     * Consultar Status da Transação (Polling do Vue)
     */
    public function checkStatus(Request $request)
    {
        $identifier = $request->token ?? $request->id ?? $request->idTransaction;

        if (!$identifier) {
            return response()->json(['status' => 0, 'paid' => false]);
        }

        $deposit = Deposit::where('payment_id', $identifier)->first();

        if ($deposit && $deposit->status === 1) {
            return response()->json([
                'status' => 1, 
                'paid' => true,
                'status_message' => 'pago com sucesso'
            ]);
        }

        return response()->json([
            'status' => 0, 
            'paid' => false,
            'status_message' => 'aguardando pagamento'
        ]);
    }
}