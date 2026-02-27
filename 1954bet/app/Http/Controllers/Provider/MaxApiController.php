<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\GamesKey;
use App\Models\User;
use App\Models\Game;
use App\Models\Provider;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MaxApiController extends Controller
{
    private static function getCreds()
    {
        $keys = GamesKey::first();
        return [
            'url'    => $keys->evergame_base_url ?? 'https://maxapigames.com/api/v2',
            'code'   => $keys->evergame_agent_code,
            'token'  => $keys->evergame_agent_token,
            'secret' => $keys->evergame_agent_secret,
        ];
    }

    public static function fetchGames()
    {
        $creds = self::getCreds();
        
        $response = Http::post($creds['url'], [
            'method'      => 'game_list',
            'agent_code'  => $creds['code'],
            'agent_token' => $creds['token']
        ]);

        if ($response->successful() && $response->json('status') == 1) {
            $games = $response->json('games');
            
            foreach ($games as $game) {
                // Cria ou atualiza o provedor dinamicamente
                $provider = Provider::firstOrCreate(
                    ['code' => strtolower($game['provider_code'])],
                    ['name' => $game['provider_code'], 'status' => 1]
                );

                // Tratamento de URL base para a imagem, conforme DOC da Max API
                $bannerPath = $game['banner'] ? "https://maxapigames.com" . $game['banner'] : '';

                Game::updateOrCreate(
                    ['game_code' => $game['game_code'], 'provider_id' => $provider->id],
                    [
                        'game_name' => $game['game_name'],
                        'cover' => $bannerPath,
                        'status' => 1,
                    ]
                );
            }
            return response()->json(['message' => 'Jogos sincronizados.']);
        }
        return response()->json(['error' => 'Falha ao buscar jogos'], 500);
    }

    public static function gameLaunch($gameCode)
    {
        $creds = self::getCreds();
        $user = auth('api')->user();

        $response = Http::post($creds['url'], [
            'method'       => 'game_launch',
            'agent_code'   => $creds['code'],
            'agent_token'  => $creds['token'],
            'user_code'    => (string) $user->id,
            'game_code'    => $gameCode,
            'callback_url' => url('/api/webhooks/maxapi/callback')
        ]);

        if ($response->successful() && $response->json('status') == 1) {
            return ['status' => true, 'url' => $response->json('launch_url')];
        }
        return ['status' => false, 'error' => $response->json('msg') ?? 'Erro ao abrir jogo'];
    }

    public function webhook(Request $request)
    {
        $creds = self::getCreds();
        $data = $request->all();

        if (($data['agent_secret'] ?? '') !== $creds['secret'] || ($data['agent_code'] ?? '') !== $creds['code']) {
            return response()->json(['status' => 0, 'msg' => 'INVALID_AGENT']);
        }

        $method = $data['method'] ?? '';
        $userCode = $data['user_code'] ?? '';

        $user = User::find($userCode);
        if (!$user) {
            return response()->json(['status' => 0, 'msg' => 'INVALID_USER']);
        }

        try {
            if ($method === 'user_balance') {
                return response()->json([
                    'status' => 1,
                    'user_balance' => (float) $user->wallet->balance
                ]);
            }

            if ($method === 'transaction') {
                $slot = $data['slot'] ?? [];
                $betMoney = $slot['bet_money'] ?? 0;
                $winMoney = $slot['win_money'] ?? 0;
                $gameCode = $slot['game_code'] ?? 'maxapi_game';
                $txnId = $slot['txn_id'] ?? uniqid();

                if ($betMoney > 0) {
                    $user->wallet->decrement('balance', $betMoney);
                    Order::create([
                        'user_id' => $user->id,
                        'session_id' => $txnId,
                        'transaction_id' => $txnId . '_bet',
                        'type' => 'bet',
                        'type_money' => 'balance',
                        'amount' => $betMoney,
                        'providers' => 'MAX API',
                        'game' => $gameCode,
                        'status' => 1,
                    ]);
                }

                if ($winMoney > 0) {
                    $user->wallet->increment('balance', $winMoney);
                    Order::create([
                        'user_id' => $user->id,
                        'session_id' => $txnId,
                        'transaction_id' => $txnId . '_win',
                        'type' => 'win',
                        'type_money' => 'balance',
                        'amount' => $winMoney,
                        'providers' => 'MAX API',
                        'game' => $gameCode,
                        'status' => 1,
                    ]);
                }

                return response()->json([
                    'status' => 1,
                    'user_balance' => (float) $user->wallet->balance
                ]);
            }
            return response()->json(['status' => 0, 'msg' => 'INVALID_METHOD']);
        } catch (\Exception $e) {
            Log::error('Max API Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 0, 'msg' => 'INTERNAL_ERROR']);
        }
    }
}