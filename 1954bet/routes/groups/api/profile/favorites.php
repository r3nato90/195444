<?php

use App\Http\Controllers\Api\Profile\FavoriteController;
use Illuminate\Support\Facades\Route;

Route::prefix('favorites')
    ->group(function () {
        Route::get('/', [FavoriteController::class, 'index']); // Lista os favoritos
        Route::post('/store', [FavoriteController::class, 'store']); // Adiciona um favorito
        Route::get('/{gameId}', [FavoriteController::class, 'destroy']); // Remove um favorito
    });
