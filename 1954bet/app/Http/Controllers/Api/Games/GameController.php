<?php

namespace App\Http\Controllers\Api\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
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
     * Exibe o provedor mapeado como PG Soft
     */
    public function index()
    {
        $providers = Provider::where('status', 1)
            ->where('code', 'pgclone') 
            ->get()
            ->map(function($provider) {
                $provider->name = "PG Soft"; 
                return $provider;
            });

        return response()->json(['providers' => $providers]);
    }

    /**
     * Exibe os jogos na Home com Busca Flexível e Imagens Corrigidas
     */
    public function featured()
    {
        // Busca jogos ativos da PG Soft vinculados à categoria 'popular'
        $games = Game::with(['provider'])
            ->where('status', 1)
            ->where('distribution', 'pgclone')
            ->whereHas('categories', function($q) {
                $q->where('slug', 'popular'); 
            })
            ->orderBy('views', 'desc')
            ->limit(60) 
            ->get()
            ->map(function($game) {
                // Força o nome do provedor para PG Soft no front-end
                if($game->provider) {
                    $game->provider->name = "PG Soft";
                }

                // Lógica de Imagem: Tenta o arquivo local (.png) -> Fallback API Oficial
                $relativePaths = ltrim($game->cover, '/');
                
                if (!empty($relativePaths) && file_exists(public_path($relativePaths))) {
                    $game->cover = asset($relativePaths);
                } else {
                    // Fallback para a URL de banners oficial da documentação 
                    $game->cover = "https://api.pgclone.com/banners/{$game->game_code}.png";
                }
                
                return $game;
            });

        return response()->json(['featured_games' => $games]);
    }

    /**
     * Categorias limpas para foco total em Slots/Populares
     */
    public function pescaria() { return response()->json(['pescaria_games' => []]); }
    public function blockchain() { return response()->json(['blockchain_games' => []]); }
    public function influencer() { return response()->json(['influencer_games' => []]); }

    /**
     * Abertura do Jogo (Launch) integrada com PGClone v1.0 
     */
    public function show(string $id)
    {
        try {
            $game = Game::with(['categories', 'provider'])->whereStatus(1)->find($id);
            
            if (!empty($game)) {
                if (auth('api')->check()) {
                    $wallet = Wallet::where('user_id', auth('api')->user()->id)->first();
                    
                    // Verificação de saldo obrigatória conforme documentação 
                    if ($wallet && floatval($wallet->total_balance) > 0) {
                        $game->increment('views');
                        $token = \Helper::MakeToken(['id' => auth('api')->user()->id, 'game' => $game->game_code]);
                        
                        if (strtolower($game->distribution) == 'pgclone') {
                            // Envia o slug (ex: fortune-tiger) para a API 
                            $responsePG = \App\Http\Controllers\Provider\PGCloneController::gameLaunch($game->game_code);
                            
                            if (isset($responsePG['status']) && $responsePG['status'] === true) {
                                return response()->json([
                                    'status' => true,
                                    'game' => $game,
                                    'gameUrl' => $responsePG['url'], // URL retornada pela API 
                                    'token' => $token
                                ]);
                            }
                            return response()->json(['error' => 'O jogo está em manutenção temporária.', 'status' => false], 200);
                        }
                    }
                    return response()->json(['error' => 'Você precisa de saldo para jogar.', 'status' => false, 'action' => 'deposit'], 200);
                }
                return response()->json(['error' => 'Por favor, faça login para jogar.', 'status' => false], 401);
            }
            return response()->json(['error' => 'Jogo não disponível.', 'status' => false], 404);
        } catch (\Exception $e) {
            Log::error("Erro Launch: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao abrir o jogo.', 'status' => false], 200); 
        }
    }

    /**
     * Listagem geral de jogos ativos
     */
    public function allGames(Request $request)
    {
        $query = Game::query()->with(['provider', 'categories']);
        $query->where('distribution', 'pgclone')->where('status', 1);
        $games = $query->paginate(50);
        
        $games->getCollection()->transform(function ($game) {
            $relativePaths = ltrim($game->cover, '/');
            if (!empty($relativePaths) && file_exists(public_path($relativePaths))) {
                $game->cover = asset($relativePaths);
            } else {
                $game->cover = "https://api.pgclone.com/banners/{$game->game_code}.png";
            }
            
            if($game->provider) { $game->provider->name = "PG Soft"; }
            return $game;
        });

        return response()->json(['games' => $games]);
    }

    /**
     * Métodos Legados e Webhooks [cite: 20, 49]
     */
    public function getGamesByProvider(Request $request)
    {
        $providerId = $request->input('provider_id');
        $games = Game::where('provider_id', $providerId)->where('status', 1)->paginate(50);
        return response()->json($games);
    }

    public function GoldApiMethodWizzePro(Request $request) { return self::WebhooksWizzePro($request); }
    public function webhookPlayFiver(Request $request) { return self::webhookPlayFiverAPI($request); }
    public function webhookApiPragmatic40(Request $request) { return self::ApiPragmaticWebhook($request); }
}