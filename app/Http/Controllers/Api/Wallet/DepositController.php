<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Traits\Gateways\SuitpayTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DepositController extends Controller
{
    use SuitpayTrait;

    /**
     * @param Request $request
     * @return array|false[]
     */
    public function submitPayment(Request $request)
    {
         
        switch ($request->gateway) {
           
            case 'suitpay':
                return self::requestQrcode($request);
            default:
                return response()->json(['error' => 'Gateway not supported'], 400);
        }
    }

    public function consultStatusTransactionPix(Request $request)
    {
        return self::consultStatusTransaction($request);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = auth('api')->id();

        // Inicia a consulta base
        $query = Deposit::whereUserId($userId);

        // Filtra por data se o parâmetro 'filter' estiver presente
        if ($request->has('filter')) {
            $filter = $request->input('filter');

            switch ($filter) {
                case 'today':
                    $startOfDay = Carbon::today();
                    $endOfDay = Carbon::tomorrow()->subSecond();
                    $query->whereBetween('created_at', [$startOfDay, $endOfDay]);
                    break;

                case 'week':
                    $startOfWeek = Carbon::now()->startOfWeek();
                    $endOfWeek = Carbon::now()->endOfWeek();
                    $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                    break;

                case 'month':
                    $startOfMonth = Carbon::now()->startOfMonth();
                    $endOfMonth = Carbon::now()->endOfMonth();
                    $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                    break;

                case 'year':
                    $startOfYear = Carbon::now()->startOfYear();
                    $endOfYear = Carbon::now()->endOfYear();
                    $query->whereBetween('created_at', [$startOfYear, $endOfYear]);
                    break;

                default:
                    // Caso o filtro não seja reconhecido, retorna todos os resultados
                    break;
            }
        }

        // Pagina os resultados
        $deposits = $query->paginate();

        return response()->json(['deposits' => $deposits], 200);
    }

}

