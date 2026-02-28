<?php

namespace App\Traits\Providers;

use App\Helpers\Core as Helper;
use App\Models\Game;
use App\Models\Provider;
use App\Models\GamesKey;
use App\Models\GGRGames;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Missions\MissionTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait WizzeProTrait
{
    use MissionTrait;

    protected static $agentCode;
    protected static $agentToken;
    protected static $agentSecretKey;
    protected static $apiEndpoint;

    public static function getCredentialsWizzePro(): bool
    {
        $setting = GamesKey::first();

        self::$agentCode        = $setting->getAttributes()['wizze_agent_code'];
        self::$agentToken       = $setting->getAttributes()['wizze_agent_token'];
        self::$agentSecretKey   = $setting->getAttributes()['wizze_agent_secret'];
        self::$apiEndpoint      = $setting->getAttributes()['wizze_api_endpoint'];

        return true;
    }


    public static function createUserWizzePro()
    {
        if(self::getCredentialsWizzePro()) {
            $postArray = [
                "method"        => "createUser",
                "agentCode"    => self::$agentCode,
                "token"   => self::$agentToken,
                "userCode"     => auth('api')->id() . '',
            ];

            $response = Http::post(self::$apiEndpoint, $postArray);

            if($response->successful()) {
                return true;
            }
            return false;
        }
        return false;
    }
    private static function GetBalanceInfoWizzePro($request)
    {
        $wallet = Wallet::where('user_id', $request->userCode)->where('active', 1)->first();
        if(!empty($wallet) && $wallet->total_balance > 0) {
            return response()->json([
                'balance' => $wallet->total_balance,
                'msg' => "SUCCESS"
            ]);
        }

        return response()->json([
            'balance' => 0,
            'msg' => "INSUFFICIENT_USER_FUNDS"
        ]);
    }
    public static function getFiversUserDetailWizzePro()
    {
        if(self::getCredentialsWizzePro()) {
            $dataArray = [
                "method"        => "call_players",
                "agentCode"    => self::$agentCode,
                "token"   => self::$agentToken,
            ];

            $response = Http::post(self::$apiEndpoint, $dataArray);

            if($response->successful()) {
                $data = $response->json();
                dd($data);
            }else{
                return false;
            }
        }
    }

    
    public static function GameLaunchWizzePro($provider_code, $game_code, $lang, $userId)
    {
        if(self::getCredentialsWizzePro()) {
            $postArray = [
                "method"        => "GetGameUrl",
                "agentCode"    => self::$agentCode,
                "token"   => self::$agentToken,
                "userCode"     => $userId.'',
                "vendorCode" => $provider_code,
                "gameCode"     => $game_code,
                "lang"          => $lang
            ];

            $response = Http::post(self::$apiEndpoint, $postArray);
            $data = $response->json();

            $endpointwo = self::$apiEndpoint . 'game_launch';

            if ($game_code === '98' || 
                $game_code === '68' || 
                $game_code === '69' || 
                $game_code === '126' || 
                $game_code === '1543462' || 
                $game_code === '1695365' || 
                $game_code === '40' || 
                $game_code === '42' ||
                $game_code === '48' || 
                $game_code === '63' || 
                $game_code === '1682240' ||
                $game_code === '104' ||
                $game_code === '1451122' || 
                $game_code === '1492288' || 
                $game_code === '1717688' ||
                $game_code === '1738001' ||
                $game_code === '1508783' ||
                $game_code === '1778752' ||
                $game_code === '1778752') {
            
                $wallet = Wallet::where('user_id', $userId)->where('active', 1)->first();

                error_log($game_code);

                switch ($game_code) {
            case '98':
                error_log("Dentro do Switch 98");
                $gamename = "fortune-ox";
                break;
            case '68':
                $gamename = "fortune-mouse";
                break;
            case '69':
                $gamename = "bikini-paradise";
                break;
            case '126':
                $gamename = "fortune-tiger";
                break;
            case '1543462':
                $gamename = "fortune-rabbit";
                break;
            case '1695365':
                $gamename = "fortune-dragon";
                break;
            case '40':
                $gamename = "jungle-delight";
                break;
            case '42':
                $gamename = "ganesha-gold";
                break;
            case '48':
                $gamename = "double-fortune";
                break;
            case '63':
                $gamename = "dragon-tiger-luck";
                break;
            case '1682240':
                $gamename = "cash-mania";
                break;
            case '1451122':
                $gamename = "dragon-hatch2";
                break;
            case '1492288':
                $gamename = "pinata-wins";
                break; 
            case '1738001':  
                $gamename = "chicky-run";
                break; 
            case '1508783':  
                $gamename = "wild-ape-3258";
                break;     
            case '1778752':  
                $gamename = "futebol-fever";
                break;     
            case '1717688':
                $gamename = "mystic-potions";
                break;
            case '104':
                $gamename = "wild-bandito";
                break;
    
                }

                $postArray = [
                    "agentToken"   => self::$agentToken,
                    "secretKey"    => self::$agentSecretKey,
                    "user_code"     => $userId.'',
                    "provider_code" => $provider_code,
                    "game_code"     => $gamename,
                    "user_balance" => $wallet->total_balance,
                    "lang"          => $lang
                ];
                $wallet = Wallet::where('user_id', $userId)->where('active', 1)->first();

                $response = Http::post($endpointwo, $postArray);
                $data = $response->json();

                $data['launchUrl'] = $data['launch_url'];
            }

            if($data['status'] == 5) {
                if($data['msg'] == 'INVALID_USER') {
                    if(self::createUserWizzePro()) {
                        return self::GameLaunchWizzePro($provider_code, $game_code, $lang, $userId);
                    }
                }
            }else{
                if($provider_code == "Evolution_Casino"){
                    $data['launchUrl'] = $data['launchUrl']."&game_id=".$game_code;
                }

                return $data;
            }
        }
    }
    public static function WebhooksWizzePro($request)
    {
        switch ($request->method) {
            case "user_balance":
                return self::GetUserBalanceWizzePro($request);
            case "game_callback":
                return self::GameCallbackWizzePro($request);
            case "money_callback":
                return self::MoneyCallbackWizzePro($request);
            default:
                return response()->json(['status' => 0]);
        }
        
    }
    private static function GetUserBalanceWizzePro($request)
    {
        $wallet = Wallet::where('user_id', $request->user_code)->where('active', 1)->first();
        if(!empty($wallet) && $wallet->total_balance > 0) {
            return response()->json([
                'status' => 1,
                'user_balance' => $wallet->total_balance
            ]);
        }

        return response()->json([
            'status' => 0,
            'user_balance' => 0,
            'msg' => "INSUFFICIENT_USER_FUNDS"
        ]);
    }
    private static function GameCallbackWizzePro($request)
    {
        $data = $request->all();
        try {
            if($data['game_type'] == 'slot' && isset($data['slot'])) {
                return self::ProcessPlayWizzePro($data, $request->user_code,'slot');
            }

            if($data['game_type'] == 'casino' && isset($data['casino'])) {
                return self::ProcessPlayWizzePro($data, $request->user_code, 'casino');
            }

            return response()->json([
                'status' => 0,
                'msg' => 'INVALID_USER	'
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
    private static function ProcessPlayWizzePro($data, $userId, $type)
    {
        $wallet = Wallet::where('user_id', $userId)->where('active', 1)->first();
        if(!empty($wallet)) {
            $game = Game::where('game_code', $data[$type]['game_code'])->first();
            self::CheckMissionExist($userId, $game);
    
            // Verificar se já existe uma transação do tipo "bet" com o mesmo ID
            $existingTransaction = Order::where('transaction_id', $data[$type]['txn_id'])->where('type', 'bet')->first();
            if(!empty($existingTransaction)) {
                return response()->json([
                    'status' => 0,
                    'user_balance' => $wallet->total_balance,
                    'msg' => 'DUPLICATED_REQUEST'
                ]);
            }
    
            // Verificar se já existe uma transação com o mesmo ID
            $transaction = Order::where('transaction_id', $data[$type]['txn_id'])->first();
            if(!empty($transaction)) {
                // Atualizar a transação existente
                $resultType = floatval($data[$type]['win']) > floatval($data[$type]['bet']) ? 'win' : 'loss';
                $transaction->update([
                    'type' => $resultType,
                    'amount' => abs($data[$type]['win'] - $data[$type]['bet']) // Diferença entre o valor ganho e a aposta
                ]);
    
                // Encontrar a última transação do usuário para o jogo específico
                $lastTransaction = GGRGames::where('user_id', $wallet->user_id)
                    ->where('provider', $data[$type]['provider_code'])
                    ->where('game', $data[$type]['game_code'])
                    ->latest()
                    ->first();
    
                // Verificar se há uma transação encontrada
                if (!empty($lastTransaction)) {
                    // Atualizar os dados da última transação
                    $lastTransaction->update([
                        'transaction_id' => $transaction->id, // Usar a mesma ID da transação existente
                        'balance_bet' => $data[$type]['bet'],
                        'balance_win' => $data[$type]['win'],
                        'type' => $resultType
                    ]);
    
                    Helper::generateGameHistory(
                        $wallet->user_id,
                        $resultType,
                        $data[$type]['win'],
                        $transaction->amount,
                        $transaction->getAttributes()['type_money'],
                        $transaction->transaction_id
                    );
    
                    $wallet = Wallet::where('user_id', $userId)->where('active', 1)->first();
                    return response()->json([
                        'status' => 1,
                        'user_balance' => $wallet->total_balance,
                    ]);
                } else {
                    // Se nenhuma transação for encontrada, retornar uma resposta de erro
                    return response()->json([
                        'status' => 0,
                        'msg' => 'No transaction found for the user'
                    ]);
                }
            }
    
            // Verificar se tem saldo
            if(floatval($wallet->total_balance) >= $data[$type]['bet']) {
    
                // Verificar se usuário tem desafio ativo
                self::CheckMissionExist($userId, $game);
    
                $transaction = self::PrepareTransactionsWizzePro(
                    $wallet->id, $userId,
                    $data[$type]['txn_id'],
                    $data[$type]['bet'],
                    $data[$type]['win'],
                    $data[$type]['game_code'],
                    $data[$type]['provider_code']
                );
    
                if($transaction) {
                    $resultType = floatval($data[$type]['win']) > floatval($data[$type]['bet']) ? 'win' : 'loss';
    
                    // Atualizar registro na tabela "orders"
                    Order::where('transaction_id', $data[$type]['txn_id'])
                        ->where('type', 'bet')
                        ->update([
                            'type' => $resultType,
                            'amount' => abs($data[$type]['win'] - $data[$type]['bet']) // Diferença entre o valor ganho e a aposta
                        ]);
    
                    // Encontrar a última transação do usuário para o jogo específico
                    $lastTransaction = GGRGames::where('user_id', $wallet->user_id)
                        ->where('provider', $data[$type]['provider_code'])
                        ->where('game', $data[$type]['game_code'])
                        ->latest()
                        ->first();
    
                    // Verificar se há uma transação encontrada
                    if (!empty($lastTransaction)) {
                        // Atualizar os dados da última transação
                        $lastTransaction->update([
                            'transaction_id' => $transaction->id, // Usar a mesma ID da transação criada
                            'balance_bet' => $data[$type]['bet'],
                            'balance_win' => $data[$type]['win'],
                            'type' => $resultType
                        ]);
    
                        Helper::generateGameHistory(
                            $wallet->user_id,
                            $resultType,
                            $data[$type]['win'],
                            $transaction->amount,
                            $transaction->getAttributes()['type_money'],
                            $transaction->transaction_id
                        );
    
                        $wallet = Wallet::where('user_id', $userId)->where('active', 1)->first();
                        return response()->json([
                            'status' => 1,
                            'user_balance' => $wallet->total_balance,
                        ]);
                    } else {
                        // Se nenhuma transação for encontrada, retornar uma resposta de erro
                        return response()->json([
                            'status' => 0,
                            'msg' => 'No transaction found for the user'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 0,
                        'msg' => 'INSUFFICIENT_USER_FUNDS'
                    ]);
                }
            }
        }
    }
    
    /**
     * Money Callback World Slot
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    private static function MoneyCallbackWizzePro($request)
    {
        $data = $request->all();

        $transaction = Order::where('transaction_id', $data['txn_id'])->first();
        $wallet = Wallet::where('user_id', $transaction->user_id)->first();

        if(!empty($transaction) && !empty($wallet)) {

        }

        return response()->json([
            'status' => 1,
            'user_balance' => $wallet->total_balance
        ]);
    }
    private static function SetTransactionWizzePro($request)
    {
        $data = $request->all();
        $wallet = Wallet::where('user_id', $data['userCode'])->where('active', 1)->first();

        if(!empty($wallet)) {
            if(isset($data['userCode'])) {
                $amount = floatval($data['amount']);

                if($amount < 0){
                    $bet = abs($amount);
                    $win = 0;
                }else{
                    $bet = 0;
                    $win = $amount;
                }

                if ($bet == 0 && $win == 0) {
                    return response()->json([
                        "status"      => 0,
                        "balance"  => floatval(number_format($wallet->total_balance, 2, '.', '')),
                        "msg"    => "SUCCESS",
                    ]);
                }

                $game = Game::where('game_id', $data['gameCode'])->first();
                $provider = Provider::where('id', $game->provider_id)->first();

                self::CheckMissionExist($data['userCode'], $game);

                $changeBonus = Helper::DiscountBalance($wallet, $bet);
                if($changeBonus != 'no_balance') {
                    return self::PrepareTransactionsWizzePro($wallet, $data['userCode'], $data['txnCode'], $bet, $win, $game->game_name, $provider->code, $changeBonus, $data['txnType']);
                }else{
                    return false;
                }
            }
        }
    }

    private static function PrepareTransactionsWizzePro($walletId, $userCode, $txnId, $betMoney, $winMoney, $gameCode, $providerCode)
    {
        $wallet = Wallet::find($walletId);
        $user = User::find($wallet->user_id);

        $typeAction  = 'bet';
        $bet = floatval($betMoney);

        $changeBonus = Helper::DiscountBalance($wallet, $bet);
        if($changeBonus != 'no_balance') {
            /// criar uma transação
            $transaction = self::CreateTransactionsWizzePro($userCode, time(), $txnId, $typeAction, $changeBonus, $bet, $gameCode, $gameCode);
            if($transaction) {
                /// salvar transação GGR
                GGRGames::create([
                    'user_id' => $userCode,
                    'provider' => $providerCode,
                    'game' => $gameCode,
                    'balance_bet' => $bet,
                    'balance_win' => 0,
                    'currency' => $wallet->currency,
                    'aggregator' => "wizzepro",
                    "type" => "loss"
                ]);

                return $transaction;
            }
        }else{
            return response()->json([
                'status' => 0,
                'msg' => "INVALID_USER"
            ]);
        }
        return false;
    }

    private static function CreateTransactionsWizzePro($playerId, $betReferenceNum, $transactionID, $type, $changeBonus, $amount, $game, $pn)
    {

        $order = Order::create([
            'user_id'       => $playerId,
            'session_id'    => $betReferenceNum,
            'transaction_id'=> $transactionID,
            'type'          => $type,
            'type_money'    => $changeBonus,
            'amount'        => $amount,
            'providers'     => 'wizzepro',
            'game'          => $game,
            'game_uuid'     => $pn,
            'round_id'      => 1,
        ]);

        if($order) {
            return $order;
        }

        return false;
    }

    public static function getProviderWizzePro()
    {
        if (self::getCredentialsWizzePro()) {
            $response = Http::post(self::$apiEndpoint, [
                'method' => 'GetVendors',
                'agentCode' => self::$agentCode,
                'token' => self::$agentToken
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['vendors'])) {
                    foreach ($data['vendors'] as $vendor) {
                        $cleanedName = explode('_', $vendor['vendorCode'])[0];
                        $checkProvider = Provider::where('code', $vendor['vendorCode'])->where('distribution', 'wizzepro')->first();
                        if (empty($checkProvider)) {
                            $dataProvider = [
                                'code' => $vendor['vendorCode'],
                                'name' => $cleanedName,
                                'rtp' => 80,
                                'status' => 1,
                                'distribution' => 'wizzepro',
                            ];
                            Provider::create($dataProvider);
                            echo "provedor criado com sucesso \n";
                        }
                    }
                }
                usleep(6000000);
            }
        }
    }

    public static function getGamesWizzePro()
    {
        if(self::getCredentialsWizzePro()) {
            $providers = Provider::where('distribution', 'wizzepro')->get();

            foreach($providers as $provider) {
                $response = Http::post(self::$apiEndpoint, [
                    'method' => 'GetVendorGames',
                    'agentCode' => self::$agentCode,
                    'token' => self::$agentToken,
                    'vendorCode' => $provider['code']
                ]);

                if($response->successful()) {
                    $data = $response->json();

                    if(isset($data['vendorGames'])) {
                        foreach ($data['vendorGames'] as $game) {
                            $gameName = str_replace('_', ' ', json_decode($game['gameName'], true)['en']);

                            $existingGame = Game::where('game_id', $game['gameCode'])->where('distribution', 'wizzepro')->first();

                            if(empty($existingGame)) {
                                if(!empty($game['imageUrl'])) {
                                    $imageUrl = json_decode($game['imageUrl'], true)['en'];
                                    $image = self::uploadFromUrlWizzePro($imageUrl, $game['gameCode']);
                                } else {
                                    $image = null;
                                }

                                if(!empty($game['gameCode']) && !empty($gameName)) {
                                    $data = [
                                        'provider_id'   => $provider->id,
                                        'game_id'       => $game['gameCode'],
                                        'game_code'     => $game['gameCode'],
                                        'game_name'     => $gameName,
                                        'technology'    => 'html5',
                                        'distribution'  => 'wizzepro',
                                        'rtp'           => 80,
                                        'cover'         => $image,
                                        'status'        => 1,
                                    ];

                                    Game::create($data);
                                    echo "jogo salvo com sucesso \n";
                                }
                            }
                        }
                    }
                }

                usleep(5000000);
            }
        }
    }

    private static function uploadFromUrlWizzePro($url, $name = null)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($url);

            if ($response->getStatusCode() === 200) {
                $fileContent = $response->getBody();

                $parsedUrl = parse_url($url);
                $pathInfo = pathinfo($parsedUrl['path']);
                $fileName  = $name ?? $pathInfo['filename'];
                $extension = $pathInfo['extension'] ?? 'png';

                $fileName = 'wizzepro/'.$fileName . '.' . $extension;

                Storage::disk('public')->put($fileName, $fileContent);

                return $fileName;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
?>
