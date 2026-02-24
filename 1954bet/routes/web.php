<?php

use App\Http\Controllers\BauController;
use App\Models\Game;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\AccountWithdrawController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|Sme
*/

Route::post('/account_withdraw', [AccountWithdrawController::class, 'store']);
Route::get('/api/musics', [MusicController::class, 'index']);

Route::put('/api/bau/{id}/abrir', [BauController::class, 'abrirBau']);

Route::get('clear', function () {
    Artisan::command('clear', function () {
        Artisan::call('optimize:clear');
        return back();
    });

    return back();
});

// GAMES PROVIDER
include_once(__DIR__ . '/groups/provider/wizzepro.php');
include_once(__DIR__ . '/groups/provider/games.php');
include_once(__DIR__ . '/groups/provider/playFiver.php');


// GATEWAYS
include_once(__DIR__ . '/groups/gateways/suitpay.php');
include_once(__DIR__ . '/groups/gateways/bspay.php');

/// SOCIAL
include_once(__DIR__ . '/groups/auth/social.php');

// APP
include_once(__DIR__ . '/groups/layouts/app.php');








