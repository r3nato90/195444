<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Wallet;
use App\Models\GamesKey;
use App\Models\Provider;
use App\Models\Category;
use App\Models\Game;

class PGCloneController extends Controller
{
    /**
     * Obtém as credenciais dinamicamente do Painel
     */
    private static function getCredentials()
    {
        $config = GamesKey::first();

        return [
            'token'  => $config ? $config->play_gaming_agent_token : env('PGCLONE_TOKEN'),
            'secret' => $config ? $config->play_gaming_agent_secret : env('PGCLONE_SECRET'),
            'code'   => $config ? $config->play_gaming_agent_code : env('PGCLONE_CODE', 'PGClone')
        ];
    }

    /**
     * Abertura do jogo (Launch) conforme Documentação v1.0 [cite: 19, 48]
     */
    public static function gameLaunch($game_code)
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return ['status' => false, 'msg' => 'Usuário não autenticado'];
            }

            $keys = self::getCredentials();
            if (empty($keys['token']) || empty($keys['secret'])) {
                return ['status' => false, 'msg' => 'Credenciais ausentes no painel admin.'];
            }

            $wallet = Wallet::where('user_id', $user->id)->first();
            $balance = $wallet ? (float) $wallet->total_balance : 0.00;

            $payload = [
                'agentToken'    => $keys['token'],
                'secretKey'     => $keys['secret'],
                'user_code'     => 'player_' . $user->id,
                'provider_code' => 'PGSOFT',
                'game_code'     => $game_code, 
                'user_balance'  => $balance,
                'language'      => 'pt'
            ];
            
            $response = Http::timeout(15)->withOptions(['verify' => false])
                ->post('https://api.pgclone.com/api/v1/game_launch', $payload);

            $data = $response->json();

            if ($response->successful() && is_array($data)) {
                if (!empty($data['launch_url'])) {
                    return ['status' => true, 'url' => $data['launch_url']];
                }
                if (!empty($data['data']) && is_string($data['data'])) {
                    return ['status' => true, 'url' => $data['data']];
                }
                return ['status' => false, 'msg' => $data['msg'] ?? 'A operadora recusou a conexão.'];
            }

            return ['status' => false, 'msg' => 'Falha de comunicação com o servidor da PGClone.'];

        } catch (\Exception $e) {
            Log::error('Exceção ao abrir jogo: ' . $e->getMessage());
            return ['status' => false, 'msg' => 'Erro interno ao processar a abertura do jogo.'];
        }
    }

    /**
     * Webhook de Saldo / Transação conforme Documentação
     */
    public function webhook(Request $request)
    {
        // 1. LOG DE ENTRADA: Registra tudo que a PGClone enviou
        Log::info('--- PGCLONE WEBHOOK INICIO ---');
        Log::info('Payload Recebido: ', $request->all());

        try {
            $payload = $request->all();

            if (!isset($payload['method'])) {
                Log::error('PGClone: Requisição rejeitada. Faltou o campo method.');
                return response()->json(['status' => 0, 'msg' => 'UNSUPPORTED_METHOD']);
            }

            // 2. IDENTIFICAÇÃO DO USUÁRIO
            $rawUserCode = $payload['user_code'] ?? '';
            $userId = str_replace('player_', '', $rawUserCode);
            Log::info("PGClone: Tentando localizar a carteira do ID: {$userId}");

            $wallet = Wallet::where('user_id', $userId)->first();

            if (!$wallet) {
                Log::error("PGClone: Carteira NÃO ENCONTRADA para o ID: {$userId}. Verifique se o ID existe.");
                return response()->json(['status' => 0, 'msg' => 'INVALID_USER']);
            }

            // 3. CONSULTA DE SALDO
            if ($payload['method'] === 'user_balance') {
                Log::info("PGClone: Saldo consultado com sucesso. Retornando: {$wallet->total_balance}");
                return response()->json([
                    'status' => 1, 
                    'msg' => 'SUCCESS',
                    'user_balance' => (float) $wallet->total_balance
                ]);
            }

            // 4. TRANSAÇÃO (Aposta e Ganho)
            if ($payload['method'] === 'transaction') {
                $slotData = $payload['slot'] ?? [];
                $txn_id   = $slotData['txn_id'] ?? null;
                $bet      = (float) ($slotData['bet'] ?? 0);
                $win      = (float) ($slotData['win'] ?? 0);

                if (!$txn_id) {
                    Log::error("PGClone: Transação bloqueada. Faltou o txn_id.");
                    return response()->json(['status' => 0, 'msg' => 'ERROR_MISSING_TXN']);
                }

                Log::info("PGClone: Processando Aposta -> Bet: R$ {$bet} | Win: R$ {$win} | TXN: {$txn_id}");

                // Verifica duplicidade para não descontar duas vezes
                $checkTxn = DB::table('historico_play')->where('txn_id', $txn_id)->first();
                if ($checkTxn) {
                    Log::info("PGClone: Transação duplicada ignorada. Retornando saldo atual.");
                    return response()->json([
                        'status' => 1, 
                        'msg' => 'SUCCESS',
                        'user_balance' => (float) $wallet->total_balance
                    ]);
                }
                
                // LÓGICA DE SALDO DIRETA
                $wallet->balance = $wallet->balance - $bet + $win;
                $wallet->save();
                Log::info("PGClone: Saldo atualizado com sucesso. Novo saldo real: {$wallet->balance}");

                // Tenta salvar o histórico (Se der erro, não trava o saldo)
                try {
                    DB::table('historico_play')->insert([
                        'id_user'     => $userId,
                        'nome_game'   => $slotData['game_code'] ?? 'PG Soft',
                        'bet_money'   => $bet,
                        'win_money'   => $win,
                        'txn_id'      => $txn_id,
                        'status_play' => 1,
                        'created_at'  => now()
                    ]);
                    Log::info("PGClone: Histórico salvo com sucesso.");
                } catch (\Exception $e) {
                    Log::error("PGClone: O saldo foi alterado, mas o histórico falhou: " . $e->getMessage());
                }
                
                Log::info('--- PGCLONE WEBHOOK SUCESSO ---');
                return response()->json([
                    'status' => 1,
                    'msg' => 'SUCCESS',
                    'user_balance' => (float) $wallet->refresh()->total_balance
                ]);
            }

            return response()->json(['status' => 0, 'msg' => 'UNSUPPORTED_METHOD']);

        } catch (\Exception $e) {
            Log::error('PGClone: Erro Fatal no Webhook: ' . $e->getMessage());
            return response()->json(['status' => 0, 'msg' => 'ERROR_QUERY']);
        }
    }
    /**
     * Sincronizar Jogos utilizando SLUGS e Mapeamento de Imagens Completo
     */
    public static function fetchGames()
    {
        try {
            // Mapeamento completo com links .webp da Fivers para garantir imagens na Home
            $fiversImages = [
                'fortune-tiger'         => 'https://imagensfivers.com/Games/Pgsoft/126.webp',
                'fortune-ox'            => 'https://imagensfivers.com/Games/Pgsoft/98.webp',
                'fortune-rabbit'        => 'https://imagensfivers.com/Games/Pgsoft/1543462.webp',
                'fortune-dragon'        => 'https://imagensfivers.com/Games/Pgsoft/1695365.webp',
                'fortune-mouse'         => 'https://imagensfivers.com/Games/Pgsoft/68.webp',
                'fortune-snake'         => 'https://imagensfivers.com/Games/Pgsoft/1879752.webp',
                'jungle-delight'        => 'https://imagensfivers.com/Games/Pgsoft/40.webp',
                'dragon-tiger-luck'     => 'https://imagensfivers.com/Games/Pgsoft/63.webp',
                'ganesha-gold'          => 'https://imagensfivers.com/Games/Pgsoft/42.webp',
                'double-fortune'        => 'https://imagensfivers.com/Games/Pgsoft/double-fortune.webp',
                'bikini-paradise'       => 'https://imagensfivers.com/Games/Pgsoft/69.webp',
                'treasures-aztec'       => 'https://imagensfivers.com/Games/Pgsoft/treasures-aztec.webp',
                'wild-bandito'          => 'https://imagensfivers.com/Games/Pgsoft/104.webp',
                'lucky-neko'            => 'https://imagensfivers.com/Games/PG-Soft/PGS_89.webp',
                'dragon-hatch'          => 'https://imagensfivers.com/Games/Pgsoft/dragon-hatch.webp',
                'cash-mania'            => 'https://imagensfivers.com/Games/Pgsoft/1682240.webp',
                'zombie-outbreak'       => 'https://imagensfivers.com/Games/Pgsoft/1635221.webp',
                'majestic-ts'           => 'https://imagensfivers.com/Games/Pgsoft/95.webp',
                'butterfly-blossom'     => 'https://imagensfivers.com/Games/Pgsoft/125.webp',
                'thai-river'            => 'https://imagensfivers.com/Games/Pgsoft/92.webp',
                'rise-of-apollo'        => 'https://imagensfivers.com/Games/Pgsoft/101.webp',
                'ninja-raccoon'         => 'https://imagensfivers.com/Games/Pgsoft/1529867.webp',
                'lucky-clover'          => 'https://imagensfivers.com/Games/Pgsoft/1601012.webp',
                'ultimate-striker'      => 'https://imagensfivers.com/Games/Pgsoft/1489936.webp',
                'prosper-ftree'         => 'https://imagensfivers.com/Games/Pgsoft/1738001.webp',
                'wild-ape'              => 'https://imagensfivers.com/Games/Pgsoft/1508783.webp'
            ];

            $keys = self::getCredentials();
            $response = Http::timeout(60)->withOptions(['verify' => false])
                ->post('https://pgclone.com/api/listgames', [
                    'agentToken' => $keys['token'],
                    'secretKey' => $keys['secret']
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success'] === true && !empty($data['games'])) {
                    
                    // Limpa registros antigos para evitar duplicidade de slugs ou códigos antigos [cite: 33]
                    Game::where('distribution', 'pgclone')->delete();

                    $provider = Provider::firstOrCreate(['code' => 'pgclone'], ['name' => 'PG Soft', 'status' => 1]);
                    $category = Category::firstOrCreate(['slug' => 'popular'], ['name' => 'Populares', 'status' => 1]);

                    foreach ($data['games'] as $gameApi) {
                        $gameCode = $gameApi['code']; 
                        
                        // Define a imagem: Prioridade absoluta para o mapa .webp da Fivers
                        $coverPath = $fiversImages[$gameCode] ?? $gameApi['banner'];

                        $game = Game::create([
                            'game_code'    => $gameCode,
                            'provider_id'  => $provider->id,
                            'game_id'      => $gameApi['id'] ?? $gameCode,
                            'game_name'    => $gameApi['name'],
                            'cover'        => $coverPath, 
                            'status'       => 1,
                            'distribution' => 'pgclone'
                        ]);
                        
                        $game->categories()->sync([$category->id]);
                    }
                    return response()->json(['message' => 'Sincronização e mapeamento de imagens concluídos!'], 200);
                }
            }
            return response()->json(['error' => 'Falha na API de listagem'], 400);
        } catch (\Exception $e) {
            Log::error('Erro na sincronização: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}