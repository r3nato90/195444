<?php

namespace App\Http\Controllers\Api\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameFavorite;
use App\Models\GameLike;
use App\Models\GamesKey;
use App\Models\Gateway;
use App\Models\Provider;
use App\Models\Wallet;
use App\Traits\Providers\WorldSlotTrait;
use App\Traits\Providers\PlayFiverTrait;
use App\Traits\Providers\ApiPragmatic40Trait;
use App\Traits\Providers\WizzeProTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Core as Helper;

class GameController extends Controller
{
    use PlayFiverTrait,
        WorldSlotTrait,
        ApiPragmatic40Trait,
        WizzeProTrait;
        


    /**
     * @dev anonymous
     * Display a listing of the resource.
     */
    public function index()
    {
        $providers = Provider::with(['games', 'games.provider'])
            ->where('status', 1)
            ->whereHas('games')
            ->orderBy('name', 'desc')
            ->get();

        return response()->json(['providers' => $providers]);
    }

    /**
     * @dev anonymous
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured()
    {
        $featured_games = Game::with(['provider'])
            ->where('is_featured', 1)
            ->where('status', 1)
            ->get();

        return response()->json(['featured_games' => $featured_games]);
    }
    public function pescaria()
    {
        $pescaria_games = Game::with(['provider'])
            ->where('is_pescaria', 1)
            ->where('status', 1)
            ->get();

        return response()->json(['pescaria_games' => $pescaria_games]);
    }
    public function blockchain()
    {
        $blockchain_games = Game::with(['provider'])
            ->where('is_blockchain', 1)
            ->where('status', 1)
            ->get();

        return response()->json(['blockchain_games' => $blockchain_games]);
    }
    public function influencer()
    {
        $influencer_games = Game::with(['provider'])
            ->where('is_influencer_mode', 1)
            ->where('status', 1)
            ->get();

        return response()->json(['influencer_games' => $influencer_games]);
    }


    /**
     * Source Provider
     *
     * @dev anonymous
     * @param Request $request
     * @param $token
     * @param $action
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function sourceProvider(Request $request, $token, $action)
    {
        $tokenOpen = \Helper::DecToken($token);
        $validEndpoints = ['session', 'icons', 'spin', 'freenum'];

        if (in_array($action, $validEndpoints)) {
            if (isset($tokenOpen['status']) && $tokenOpen['status']) {
                $game = Game::whereStatus(1)->where('game_code', $tokenOpen['game'])->first();
                if (!empty($game)) {
                    $controller = \Helper::createController($game->game_code);

                    switch ($action) {
                        case 'session':
                            return $controller->session($token);
                        case 'spin':
                            return $controller->spin($request, $token);
                        case 'freenum':
                            return $controller->freenum($request, $token);
                        case 'icons':
                            return $controller->icons();
                    }
                }
            }
        } else {
            return response()->json([], 500);
        }
    }

    /**
     * @dev anonymous
     * Store a newly created resource in storage.
     */
    public function toggleFavorite($id)
    {
        if (auth('api')->check()) {
            $checkExist = GameFavorite::where('user_id', auth('api')->id())->where('game_id', $id)->first();
            if (!empty($checkExist)) {
                if ($checkExist->delete()) {
                    return response()->json(['status' => true, 'message' => 'Removido com sucesso']);
                }
            } else {
                $gameFavoriteCreate = GameFavorite::create([
                    'user_id' => auth('api')->id(),
                    'game_id' => $id
                ]);

                if ($gameFavoriteCreate) {
                    return response()->json(['status' => true, 'message' => 'Criado com sucesso']);
                }
            }
        }
    }

    /**
     * @dev anonymous
     * Store a newly created resource in storage.
     */
    public function toggleLike($id)
    {
        if (auth('api')->check()) {
            $checkExist = GameLike::where('user_id', auth('api')->id())->where('game_id', $id)->first();
            if (!empty($checkExist)) {
                if ($checkExist->delete()) {
                    return response()->json(['status' => true, 'message' => 'Removido com sucesso']);
                }
            } else {
                $gameLikeCreate = GameLike::create([
                    'user_id' => auth('api')->id(),
                    'game_id' => $id
                ]);

                if ($gameLikeCreate) {
                    return response()->json(['status' => true, 'message' => 'Criado com sucesso']);
                }
            }
        }
    }

    /**
     * @dev anonymous
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $game = Game::with(['categories', 'provider'])->whereStatus(1)->find($id);
        if (!empty($game)) {
            if (auth('api')->check()) {
                $wallet = Wallet::where('user_id', auth('api')->user()->id)->first();
                if ($wallet->total_balance > 0) {
                    $game->increment('views');

                    $token = \Helper::MakeToken([
                        'id' => auth('api')->user()->id,
                        'game' => $game->game_code
                    ]);

                    switch ($game->distribution) {
                        case 'source':
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => url('/originals/' . $game->game_code . '/index.html?token=' . $token),
                                'token' => $token
                            ]);
                        case 'worldslot':
                            $worldslotLaunch = self::GameLaunchWorldSlot($game->provider->code, $game->game_id, 'pt', auth('api')->id());

                            if (isset($worldslotLaunch['launch_url'])) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $worldslotLaunch['launch_url'],
                                    'token' => $token
                                ]);
                            }

                            return response()->json(['error' => $worldslotLaunch, 'status' => false], 400);
                        case 'wizzepro':
                            $wizzeproLaunch = self::GameLaunchWizzePro($game->provider->code, $game->game_id, 'pt', auth('api')->id());
                            
                            if(isset($wizzeproLaunch['launchUrl'])) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $wizzeproLaunch['launchUrl'],
                                ]);
                            } else {
                                return response()->json($wizzeproLaunch);
                            }
                    
                        return response()->json(['error' => $wizzeproLaunch, 'status' => false ], 400);
                        case 'apipragmatic40':
                        $apiPragmatic40Launch = self::ApiPragmaticGameLaunch($game->provider->code, $game->game_id, 'pt', auth('api')->user()->email);
                        if(isset($apiPragmatic40Launch['launch_url'])) {
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => $apiPragmatic40Launch['launch_url'],
                                'token' => $token
                            ]);
                        }
                    
                        return response()->json(['error' => $apiPragmatic40Launch, 'status' => false ], 400);
                        case 'play_fiver':
                            $playfiver = self::playFiverLaunch($game->game_id, $game->only_demo);
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => $playfiver['launch_url'],
                                'token' => $token
                            ]);
                        

                    }
                }
                return response()->json(['error' => 'Você precisa ter saldo para jogar', 'status' => false, 'action' => 'deposit'], 200);
            }
            return response()->json(['error' => 'Você precisa tá autenticado para jogar', 'status' => false], 400);
        }
        return response()->json(['error' => '', 'status' => false], 400);
    }

    /**
     * @dev anonymous
     * Show the form for editing the specified resource.
     */
    public function allGames(Request $request)
    {
        $query = Game::query();
        $query->with(['provider', 'categories']);

        if (!empty($request->provider) && $request->provider != 'all') {
            $query->where('provider_id', $request->provider);
        }

        if (!empty($request->category) && $request->category != 'all') {
            $query->whereHas('categories', function ($categoryQuery) use ($request) {
                $categoryQuery->where('slug', $request->category);
            });
        }

        if (isset($request->searchTerm) && !empty($request->searchTerm) && strlen($request->searchTerm) > 2) {
            $query->whereLike(['game_code', 'game_name', 'description', 'distribution', 'provider.name'], $request->searchTerm);
        } else {
            $query->orderBy('views', 'desc');
        }

        $games = $query
            ->where('status', 1)
            ->paginate(30)->appends(request()->query());

        return response()->json(['games' => $games]);
    }

    public function getGamesByProvider(Request $request)
    {
        $providerId = $request->input('provider_id');
        $perPage = $request->input('per_page', 6);
        $games = Game::where('provider_id', $providerId)->where('status', 1)->paginate($perPage);
        return response()->json($games);
    }
    // WIZZE PRO API
    public function GoldApiMethodWizzePro(Request $request)
    {
        return self::WebhooksWizzePro($request);
    }
    public function UserBalanceMethodWizzePro(Request $request)
    {
        return self::GetUserBalanceWizzePro($request);
    }
    public function GameCallbackMethodWizzePro(Request $request)
    {
        return self::GameCallbackWizzePro($request);
    }
    public function MoneyCallbackMethodWizzePro(Request $request)
    {
        return self::MoneyCallbackWizzePro($request);
    }
    public function webhookPlayFiver(Request $request)
    {
        return self::webhookPlayFiverAPI($request);
    }
    public function webhookGoldApiMethod(Request $request)
    {
        return self::WebhooksFivers($request);
    }
    public function webhookApiPragmatic40(Request $request)
    {
     return self::ApiPragmaticWebhook($request);
    }


   

}
