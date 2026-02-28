<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class LastWinnersController extends Controller
{
    public function index()
    {
        // Busca todos os pedidos do tipo 'win' com os joins necessários
        $orders = Order::select(
                'orders.*',
                'games.game_name as game_name',
                'games.cover as cover',
                'users.name as user_first_name',
                'users.last_name as user_last_name'
            )
            ->join('games', 'orders.game_uuid', '=', 'games.game_id')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.type', 'win')
            ->orderBy('orders.created_at', 'desc')
            ->get();

        /*
         * Remove duplicações que possam ter ocorrido devido aos joins.
         * Usando o campo 'id' (chave primária da order) garantimos que cada order seja exibida apenas uma vez.
         * Assim, se o usuário tiver duas orders (dois ganhos), ambas serão mostradas.
         */
        $uniqueOrders = $orders->unique('id')->values()->take(25);

        // Formata os dados para o formato esperado pelo componente Vue
        $data = $uniqueOrders->map(function ($order) {
            return [
                'player' => $order->user_first_name . ' ' . $order->user_last_name,
                'game'   => $order->game_name,
                'amount' => 'R$ ' . number_format($order->amount, 2, ',', '.'),
                // Monta a URL completa da imagem usando a pasta 'storage' que é exposta publicamente
                'image'  => asset('storage/' . $order->cover),
            ];
        });

        return response()->json($data);
    }
}
