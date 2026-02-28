<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\GameFavorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
    
        // Obtém os favoritos do usuário
        $favorites = GameFavorite::where('user_id', $user->id)
            ->with('game') // Certifique-se de ter uma relação 'game' no modelo GameFavorite
            ->get();
    
        return response()->json([
            'games' => $favorites,
        ], 200);
    }
    

    


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    
    }

    /**
     * Adiciona um jogo aos favoritos do usuário autenticado.
     */
    public function store(Request $request)
    {
        $request->validate([
            'gameId' => 'required|exists:games,id', // Valida que o gameId existe na tabela games
        ]);

        $user = auth('api')->user();
        $gameId = $request->gameId;

        // Verifica se o jogo já está favoritado
        $exists = GameFavorite::where('user_id', $user->id)
            ->where('game_id', $gameId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Jogo já está favoritado.'], 400);
        }

        // Adiciona o jogo aos favoritos
        $favorite = GameFavorite::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
        ]);

        return response()->json($favorite, 201);
    }

    /**
     * Display the specified resource.
     */
    public function destroy($gameId)
{
    // Obtém o usuário autenticado
    $user = auth('api')->user();

    // Verifica se o jogo existe nos favoritos do usuário
    $favorite = GameFavorite::where('user_id', $user->id)
        ->where('game_id', $gameId)
        ->first();

    // Se o jogo não está nos favoritos, retorna um erro
    if (!$favorite) {
        return response()->json(['message' => 'Jogo não encontrado nos favoritos.'], 404);
    }

    // Deleta o jogo dos favoritos
    $favorite->delete();

    // Retorna uma resposta de sucesso
    return response()->json(['message' => 'Jogo removido dos favoritos com sucesso.'], 200);
}


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

  
}
