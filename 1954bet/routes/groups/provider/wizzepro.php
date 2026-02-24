<?php

use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;
Route::post('/callback', [GameController::class, 'webhookApiPragmatic40']);

Route::post('wizzepro/gold_api', [GameController::class, 'GoldApiMethodWizzePro']);

Route::post('wizzepro/gold_api/user_balance', [GameController::class, 'UserBalanceMethodWizzePro']);
Route::post('wizzepro/gold_api/game_callback', [GameController::class, 'GameCallbackMethodWizzePro']);
Route::post('wizzepro/gold_api/money_callback', [GameController::class, 'MoneyCallbackMethodWizzePro']);
