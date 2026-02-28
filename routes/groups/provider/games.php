<?php

use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;


// Worldslot
Route::post('gold_api/game_callback', [GameController::class, 'webhookGameCallbackMethodClone']);
Route::post('gold_api/money_callback', [GameController::class, 'webhookMoneyCallbackMethodClone']);
Route::post('gold_api/user_balance', [GameController::class, 'webhookUserBalanceMethodClone']);