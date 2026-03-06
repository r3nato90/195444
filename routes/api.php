<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importação de Controllers
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Missions\MissionController;
use App\Http\Controllers\Api\Profile\AffiliateController;
use App\Http\Controllers\Api\Profile\VipController;
use App\Http\Controllers\Api\Profile\WalletController;
use App\Http\Controllers\Api\Wallet\DepositController; 
use App\Http\Controllers\BauController;
use App\Http\Controllers\SliderTextController;
use App\Http\Controllers\DepositanteController;
use App\Http\Controllers\AccountWithdrawController;
use App\Http\Controllers\SenSaqueController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MissionDepositController;
use App\Http\Controllers\Gateway\PixupWebhookController;
use App\Http\Controllers\Gateway\WishpagWebhookController;
use App\Http\Controllers\Gateway\VersellWebhookController;
use App\Http\Controllers\PostNotificationsController;
use App\Http\Controllers\BonusInitialController;

/*
|--------------------------------------------------------------------------
| Webhooks Callbacks (Sempre no topo, sem auth)
|--------------------------------------------------------------------------
*/
Route::post('/gateways/pixup/webhook', [PixupWebhookController::class, 'handleWebhook']);
Route::post('/gateways/wishpag/webhook', [WishpagWebhookController::class, 'handle']);
Route::post('/gateways/versell/webhook', [VersellWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| API Routes (Públicas - Essenciais para carregar o site e jogos)
|--------------------------------------------------------------------------
*/
// Configurações do Site (Resolve o erro de bt_5_link e ícones nulos)
Route::prefix('settings')->group(function () {
    include_once(__DIR__ . '/groups/api/settings/settings.php');
    include_once(__DIR__ . '/groups/api/settings/banners.php');
    include_once(__DIR__ . '/groups/api/settings/currency.php');
    include_once(__DIR__ . '/groups/api/settings/bonus.php');
});

// Categorias e Jogos (Resolve o erro do .map() nas categorias)
Route::prefix('categories')->group(function () {
    include_once(__DIR__ . '/groups/api/categories/index.php');
});
include_once(__DIR__ . '/groups/api/games/index.php');

// Outras rotas públicas
Route::get('/check-deposit-min', [BonusInitialController::class, 'checkDeposit']);
Route::get('/mission-deposits', [MissionDepositController::class, 'index']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/post-notifications', [PostNotificationsController::class, 'index']);
Route::get('/slider-text', [SliderTextController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Grupos de Rotas Autenticadas (JWT)
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth.jwt']], function () {
    
    // PERFIL E AFILIADOS
    Route::prefix('profile')->group(function () {
        include_once(__DIR__ . '/groups/api/profile/profile.php');
        include_once(__DIR__ . '/groups/api/profile/affiliates.php');
        include_once(__DIR__ . '/groups/api/profile/wallet.php');
        include_once(__DIR__ . '/groups/api/profile/likes.php');
        include_once(__DIR__ . '/groups/api/profile/favorites.php');
        include_once(__DIR__ . '/groups/api/profile/recents.php');
        include_once(__DIR__ . '/groups/api/profile/vip.php');
        include_once(__DIR__ . '/groups/api/profile/chest.php');
        include_once(__DIR__ . '/groups/api/profile/infos.php');
    });

    // CARTEIRA E DEPÓSITO (Com Sequestro PixUP)
    Route::prefix('wallet')->group(function () {
        // 1. Primeiro carrega os arquivos originais do sistema
        include_once(__DIR__ . '/groups/api/wallet/deposit.php');
        include_once(__DIR__ . '/groups/api/wallet/withdraw.php');

        // 2. SEQUESTRO: Sobrescreve as rotas de depósito para usar o nosso DepositController
        Route::post('/deposit/payment', [DepositController::class, 'submitPayment']);
        Route::get('/deposit', [DepositController::class, 'index']);
    });

    include_once(__DIR__ . '/groups/api/missions/mission.php');
});

/*
|--------------------------------------------------------------------------
| Status de Transação (Polling do Vue)
|--------------------------------------------------------------------------
| Rotas que o Vue chama para verificar se o pagamento foi concluído.
*/
Route::post('/suitpay/check-payment', [DepositController::class, 'checkStatus']);
Route::match(['get', 'post'], '/transaction/status/by-token', [DepositController::class, 'checkStatus']);

/*
|--------------------------------------------------------------------------
| Outros Grupos de Sistema
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    include_once(__DIR__ . '/groups/api/auth/auth.php');
});

include_once(__DIR__ . '/groups/api/gateways/suitpay.php');

// Rota de busca
Route::prefix('search')->group(function () {
    include_once(__DIR__ . '/groups/api/search/search.php');
});