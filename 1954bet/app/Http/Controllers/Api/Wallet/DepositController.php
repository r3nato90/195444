<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PixupController;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function submitPayment(Request $request)
    {
        // Sequestro: Front-end manda suitpay, nós rodamos PixUP
        if ($request->gateway === 'suitpay' || $request->gateway === 'pixup') {
            $pixup = new PixupController();
            return $pixup->generateQRCode($request);
        }

        return response()->json(['status' => false, 'error' => 'Gateway Inválido'], 200);
    }

    public function index()
    {
        $deposits = Deposit::where('user_id', auth('api')->id())->orderBy('id', 'desc')->paginate(15);
        return response()->json(['status' => true, 'deposits' => $deposits], 200);
    }
}