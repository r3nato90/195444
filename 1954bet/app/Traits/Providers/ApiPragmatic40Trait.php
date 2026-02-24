<?php

namespace App\Traits\Providers;

use App\Helpers\Core as Helper;
use App\Models\Game;
use App\Models\GamesKey;
use App\Models\GGRGames;
use App\Models\GGRGamesFiver;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Missions\MissionTrait;
use DateTime;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait ApiPragmatic40Trait
{
    use MissionTrait;

    /**
     * * Criado por @thigasdev -> https://t.me/thigasdev
     * @var string
     */
    protected static $agentCode;
    protected static $agentToken;
    protected static $agentSecretKey;
    protected static $apiEndpoint;

    /**
     * * Criado por @thigasdev -> https://t.me/thigasdev
     * @return void
     */
    public static function ApiPragmaticGetCredential(): bool
    {
        $setting = GamesKey::first();

        self::$agentCode        = $setting->getAttributes()['apipragmatic40_code'];
        self::$agentToken       = $setting->getAttributes()['apipragmatic40_token'];
        self::$agentSecretKey   = $setting->getAttributes()['apipragmatic40_secret'];
        self::$apiEndpoint      = $setting->getAttributes()['apipragmatic40_url'];

        return true;
    }

    /**
     * * Criado por @thigasdev -> https://t.me/thigasdev
     * @return \Illuminate\Http\JsonResponse|void
     */
    public static function ApiPragmaticWebhook($request)
    {


        switch ($request->method) {
            case "user_balance":
                return self::ApiPragmaticGetBalance($request);
            case "transaction":
                return self::ApiPragmaticSetTransaction($request);
            case "game_start":
                return self::ApiPragmaticSetGameStart($request);
            case "game_end":
                return self::ApiPragmaticSetGameEnd($request);
            default:
                return response()->json(['status' => 0]);
        }
    }


    public static function ApiPragmaticGetBalance($request)
    {
        $user = User::where('email', $request->input('user_code'))->first();
    
        if (!$user) {
            return response()->json([
                'status' => 0,
                'user_balance' => 0,
                'msg' => "USER_NOT_FOUND"
            ], 404, ['Content-Type' => 'application/json']);
        }
    
        $wallet = Wallet::where('user_id', $user->id)
                        ->where('active', 1)
                        ->first();
    
        if (!empty($wallet)) {
            // Soma o saldo das colunas balance e balance_withdrawal
            $totalBalance = floatval($wallet->balance) + floatval($wallet->balance_withdrawal) + floatval($wallet->balance_bonus);
            // Arredonda o resultado para duas casas decimais
            $totalBalance = round($totalBalance, 2);
    
            if ($totalBalance > 0) {
                return response()->json([
                    'status' => 1,
                    'user_balance' => $totalBalance
                ], 200, ['Content-Type' => 'application/json']);
            }
        }
    
        return response()->json([
            'status' => 0,
            'user_balance' => 0,
            'msg' => "INSUFFICIENT_USER_FUNDS"
        ], 200, ['Content-Type' => 'application/json']);
    }

   public static function ApiPragmaticSetTransaction($request)
{
    $data = $request->all();
    $user = User::where('email', $request->input('user_code'))->first();
    $wallet = Wallet::where('user_id', $user->id)->where('active', 1)->first();

    if (!empty($wallet)) {
        if ($data['game_type'] == 'slot' && isset($data['slot'])) {
            $game = Game::where('game_code', $data['slot']['game_code'])->first();

            // Verificar se o usuário tem desafio ativo
            self::CheckMissionExist($user->id, $game, 'fivers');

            $winMoney = (floatval($data['slot']['win_money']) - floatval($data['slot']['bet_money']));
            if (floatval($data['slot']['win_money']) > 0) {
                // Atualizar balance_withdrawal com o valor do balance e do ganho, e depois zerar balance
                $wallet->update([
                    'balance_withdrawal' => floatval($wallet->balance_withdrawal) + floatval($wallet->balance) + floatval($data['slot']['win_money']),
                    'balance' => 0 // Zerar o balance
                ]);
            }

            // Recarregar a carteira atualizada
            $wallet = Wallet::where('user_id', $user->id)->where('active', 1)->first();

            $transactions = self::ApiPragmaticPrepareTransactions(
                $wallet, 
                $user->id, 
                $data['slot']['txn_id'], 
                $data['slot']['bet_money'], 
                $winMoney, 
                $data['slot']['game_code'], 
                $data['slot']['provider_code']
            );

            if ($transactions) {
                return response()->json([
                    'status' => 1,
                ]);
            } else {
                return response()->json([
                    'status' => 0,
                    'msg' => 'INSUFFICIENT_USER_FUNDS'
                ]);
            }
        }

        if ($data['game_type'] == 'live' && isset($data['live'])) {
            $game = Game::where('game_code', $data['live']['game_code'])->first();

            // Verificar se o usuário tem desafio ativo
            self::CheckMissionExist($user->id, $game, 'fivers');

            $transactions = self::ApiPragmaticPrepareTransactions(
                $wallet, 
                $user->id, 
                $data['live']['txn_id'], 
                $data['live']['bet_money'], 
                $data['live']['win_money'], 
                $data['live']['game_code'], 
                $data['live']['provider_code']
            );
            if ($transactions) {
                return response()->json([
                    'status' => 1,
                ]);
            } else {
                return response()->json([
                    'status' => 0,
                    'msg' => 'INSUFFICIENT_USER_FUNDS'
                ]);
            }
        }
    }
}

    public static function ApiPragmaticGameLaunch($provider_code, $game_code, $lang, $userEmail)
    {
        self::ApiPragmaticGetCredential();

        $data = [
            "method" => "game_launch",
            "agent_code" => self::$agentCode,
            "agent_token" => self::$agentToken,
            "user_code" => $userEmail . '',
            "provider_code" => $provider_code,
            "game_code" => $game_code,
            "lang" => $lang
        ];
        $response = Http::post(self::$apiEndpoint, $data);


        if ($response->json()['status'] === 0) {
            $resp = $response->json();
            if ($resp['msg'] == 'INVALID_USER') {
                if (self::ApiPragmaticUserCreate()) {
                    return self::ApiPragmaticGameLaunch($provider_code, $game_code, $lang, $userEmail);
                }
            }
        } else {
            return $response->json();
        }
    }
    public static function ApiPragmaticUserCreate()
    {
        self::ApiPragmaticGetCredential();

        $data = [
            "method" => "user_create",
            "agent_code" => self::$agentCode,
            "agent_token" => self::$agentToken,
            "user_code" => auth('api')->user()->email . '',
        ];

        $response = Http::post(self::$apiEndpoint, $data);
        if ($response->successful()) {
            return true;
        } else {
            return false;
        }
    }
    public static function ApiPragmaticUserDeposit($userEmail, $ammount)
    {
        self::ApiPragmaticGetCredential();
        $data = [
            "method" => "user_deposit",
            "agent_code" => self::$agentCode,
            "agent_token" => self::$agentToken,
            "user_code" => $userEmail . "",
            "amount" => $ammount
        ];

        $response = Http::post(self::$apiEndpoint, $data);
        if ($response->successful()) {
            return $response->json();
        } else {
            return false;
        }
    }
    public static function ApiPragmaticUserWithdraw($userEmail, $ammount)
    {
        self::ApiPragmaticGetCredential();
        $data = [
            "method" => "user_withdraw",
            "agent_code" => self::$agentCode,
            "agent_token" => self::$agentToken,
            "user_code" => $userEmail . "",
            "amount" => $ammount
        ];

        $response = Http::post(self::$apiEndpoint, $data);
        if ($response->successful()) {
            return $response->json();
        } else {
            return false;
        }
    }
    public static function ApiPragmaticWhitdrawReset($userEmail)
    {
        self::ApiPragmaticGetCredential();
        $data = [
            "method" => "user_withdraw_reset",
            "agent_code" => self::$agentCode,
            "agent_token" => self::$agentToken,
            "user_code" => $userEmail . ""
        ];

        $response = Http::post(self::$apiEndpoint, $data);
        if ($response->successful()) {
            return $response->json();
        } else {
            return false;
        }
    }
    public static function ApiPragmaticMoneyInfo($userEmail)
    {
        self::ApiPragmaticGetCredential();
        $data = [
            "method" => "money_info",
            "agent_code" => self::$agentCode,
            "agent_token" => self::$agentToken,
            "user_code" => $userEmail . "",
        ];

        $response = Http::post(self::$apiEndpoint, $data);
        if ($response->successful()) {
            return $response->json();
        } else {
            return false;
        }
    }
    public static function ApiPragmaticProviderList()
    {
        self::ApiPragmaticGetCredential();
        $data = [
            "method" => "provider_list",
            "agent_code" => self::$agentCode,
            "agent_token" => self::$agentToken,
        ];

        $response = Http::post(self::$apiEndpoint, $data);
        if ($response->successful()) {
            return $response->json();
        } else {
            return false;
        }
    }
    public static function ApiPragmaticGameList($provider_code)
    {
        self::ApiPragmaticGetCredential();
        $data = [
            "method" => "game_list",
            "agent_code" => self::$agentCode,
            "agent_token" => self::$agentToken,
            "provider_code" => $provider_code
        ];

        $response = Http::post(self::$apiEndpoint, $data);
        if ($response->successful()) {
            return $response->json();
        } else {
            return false;
        }
    }
    public static function ApiPragmaticGameLog($userEmail, $game_type, $startDate, $endDate, $page, $perPage)
    {
        $start = new DateTime($startDate);
        $start = $start->format('Y-m-d H:i:s');

        $end = new DateTime($endDate);
        $end = $end->format('Y-m-d H:i:s');

        self::ApiPragmaticGetCredential();
        $data = [
            "method" => "get_game_log",
            "agent_code" => self::$agentCode,
            "agent_token" => self::$agentToken,
            "user_code" => $userEmail . "",
            "game_type" => $game_type,
            "start" => $start,
            "end" => $end,
            "page" => $page,
            "perPage" => $perPage,

        ];

        $response = Http::post(self::$apiEndpoint, $data);
        if ($response->successful()) {
            return $response->json();
        } else {
            return false;
        }
    }
    
    private static function ApiPragmaticPrepareTransactions($wallet, $userCode, $txnId, $betMoney, $winMoney, $gameCode, $providerCode)
    {
        $user = User::find($wallet->user_id);
        $typeAction  = 'bet';
        $changeBonus = 'balance';
        
        // Converte o valor da aposta para float
        $betAmount = floatval($betMoney);
    
        // Deduz o valor da aposta do saldo disponível
        if ($wallet->balance_bonus >= $betAmount) {
            $wallet->decrement('balance_bonus', $betAmount);
            $changeBonus = 'balance_bonus';
        } elseif ($wallet->balance >= $betAmount) {
            $wallet->decrement('balance', $betAmount);
            $changeBonus = 'balance';
        } elseif ($wallet->balance_withdrawal >= $betAmount) {
            $wallet->decrement('balance_withdrawal', $betAmount);
            $changeBonus = 'balance_withdrawal';
        } else {
            return false;
        }
    
        /* 
          Se houver ganho (ou seja, se o valor de win_money for maior que 0) 
          então trata-se de uma transação de vitória.
          (Note que antes era calculado o ganho líquido com 
           $winMoney = win_money - bet_money, o que pode não refletir a realidade desejada.)
        */
        if (floatval($winMoney) > 0) {
            $typeAction = 'win';
            // Cria a transação passando o valor apostado (pode-se alterar para usar outro valor, se necessário)
            $transaction = self::ApiPragmaticCreateTransactions(
                $userCode,
                time(),
                $txnId,
                $typeAction,
                $changeBonus,
                $betAmount,
                $gameCode,
                $gameCode
            );
    
            if (!empty($transaction)) {
                // Salva também os detalhes na tabela de histórico (GGRGamesFiver)
                GGRGamesFiver::create([
                    'user_id'     => $userCode,
                    'provider'    => $providerCode,
                    'game'        => $gameCode,
                    'balance_bet' => $betAmount,
                    'balance_win' => floatval($winMoney),
                    'currency'    => $wallet->currency
                ]);
    
                Helper::generateGameHistory($user->id, $typeAction, floatval($winMoney), $betAmount, $changeBonus, $txnId);
    
                return response()->json([
                    'status'       => 1,
                    'user_balance' => $wallet->total_balance
                ]);
            }
        }
    
        // Caso seja uma aposta sem ganho (ou perda) – cria a transação se ela ainda não existir
        $checkTransaction = Order::where('transaction_id', $txnId)->first();
        if (empty($checkTransaction)) {
            $checkTransaction = self::ApiPragmaticCreateTransactions(
                $userCode,
                time(),
                $txnId,
                $typeAction,
                $changeBonus,
                $betAmount,
                $gameCode,
                $gameCode
            );
        }
    
        GGRGamesFiver::create([
            'user_id'     => $userCode,
            'provider'    => $providerCode,
            'game'        => $gameCode,
            'balance_bet' => $betAmount,
            'balance_win' => 0,
            'currency'    => $wallet->currency // Use sempre o mesmo campo de currency
        ]);
    
        Helper::lossRollover($wallet, $betAmount);
        Helper::generateGameHistory($user->id, 'loss', floatval($winMoney), $betAmount, $changeBonus, $checkTransaction->transaction_id);
    
        return response()->json([
            'status'       => 1,
            'user_balance' => $wallet->total_balance
        ]);
    }
    
    private static function ApiPragmaticCreateTransactions($playerId, $betReferenceNum, $transactionID, $type, $changeBonus, $amount, $game, $pn)
    {
        $order = Order::create([
            'user_id'        => $playerId,
            'session_id'     => $betReferenceNum,
            'transaction_id' => $transactionID,
            'type'           => $type,
            'type_money'     => $changeBonus,
            'amount'         => floatval($amount), // conversão para float garante que o valor seja salvo corretamente
            'providers'      => 'apipragmatic40',
            'game'           => $game,
            'game_uuid'      => $pn,
            'round_id'       => 1,
        ]);
    
        return $order ? $order : false;
    }

}
