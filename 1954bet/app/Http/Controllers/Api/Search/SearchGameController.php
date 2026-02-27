<?php

namespace App\Http\Controllers\Api\Search;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchGameController extends Controller
{
    /**
     * Realiza a busca de jogos blindada contra erros de banco de dados
     */
    public function index(Request $request)
    {
        try {
            $query = Game::query();
            $query->with(['provider']);

            // Filtro de busca nativo e seguro
            if (isset($request->searchTerm) && !empty($request->searchTerm) && strlen($request->searchTerm) > 2) {
                $term = '%' . $request->searchTerm . '%';
                
                $query->where(function($q) use ($term) {
                    $q->where('game_code', 'like', $term)
                      ->orWhere('game_name', 'like', $term)
                      ->orWhere('description', 'like', $term);
                });
            }

            // Garante que mostre apenas jogos ativos
            $query->where('status', 1);
            
            // Ordenação por ID (descendente) para mostrar os jogos novos/sincronizados primeiro
            // Removido o 'views' que causava erro 500
            $games = $query->orderBy('id', 'desc')->paginate(12)->appends(request()->query());

            return response()->json(['games' => $games], 200);

        } catch (\Exception $e) {
            Log::error('Erro na Busca de Jogos (SearchGameController): ' . $e->getMessage());
            
            // Retorno de segurança para o Vue.js não travar a tela roxa
            return response()->json([
                'games' => [
                    'current_page' => 1,
                    'data' => [],
                    'total' => 0,
                    'last_page' => 1
                ]
            ], 200);
        }
    }

    public function create() {}
    public function store(Request $request) {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}