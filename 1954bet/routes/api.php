<?php

use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\BauController;
use App\Http\Controllers\SliderTextController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Missions\MissionController;
use App\Http\Controllers\Api\Profile\AffiliateController;
use App\Http\Controllers\AccountWithdrawController;
use App\Http\Controllers\SenSaqueController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Api\Profile\VipController;
use App\Http\Controllers\MissionDepositController;
use App\Http\Controllers\Gateway\SuitPayController;
use App\Http\Controllers\PostNotificationsController;
use App\Http\Controllers\BonusInitialController;
use App\Http\Controllers\Api\Profile\WalletController;
use App\Http\Controllers\Api\LastWinnersController;

/*
|--------------------------------------------------------------------------
| ROTA DE MANUTENÇÃO E CACHE
|--------------------------------------------------------------------------
*/
Route::get('/limpar-cache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        return response()->json([
            'status' => 'sucesso', 
            'msg' => 'Cache total limpo! O sistema foi atualizado.'
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'erro', 'msg' => $e->getMessage()]);
    }
});

/*
|--------------------------------------------------------------------------
| API PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('last-winners', [LastWinnersController::class, 'index']);
Route::get('/check-deposit-min', [BonusInitialController::class, 'checkDeposit']);
Route::get('/mission-deposits', [MissionDepositController::class, 'index']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/post-notifications', [PostNotificationsController::class, 'index']);
Route::get('/slider-text', [SliderTextController::class, 'index']);

// Auth Google
Route::get('auth/login/google', [AuthController::class, 'loginWithGoogle'])->middleware('web');
Route::get('auth/login/google/callback', [AuthController::class, 'handleGoogleCallback'])->middleware('web');

/*
|--------------------------------------------------------------------------
| WEBHOOKS DE JOGOS (Seamless Callbacks)
|--------------------------------------------------------------------------
| Blindagem: Usando 'any' para evitar que o servidor rejeite requisições 
| do tipo GET (comuns em consultas de saldo) ou OPTIONS.
*/
Route::any('webhooks/pgclone/callback', [\App\Http\Controllers\Provider\PGCloneController::class, 'webhook']);
Route::any('webhooks/maxapi/callback', [\App\Http\Controllers\Provider\MaxApiController::class, 'webhook']);

/*
|--------------------------------------------------------------------------
| WEBHOOKS DE PAGAMENTO
|--------------------------------------------------------------------------
*/
Route::post('/pixup/webhook', [\App\Http\Controllers\Gateway\PixupController::class, 'webhook']);
Route::post('webhooks/versell/cashin', [\App\Http\Controllers\Gateway\VersellController::class, 'webhookCashIn']);
Route::post('webhooks/wishpag/cashin', [\App\Http\Controllers\Gateway\WishPagController::class, 'webhookCashIn']);
Route::post('webhooks/wishpag/cashout', [\App\Http\Controllers\Gateway\WishPagController::class, 'webhookCashOut']);

/*
|--------------------------------------------------------------------------
| ROUTES WITH AUTH:API (JWT)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {
    // Wallet & Balance
    Route::get('profile/wallet/update-bonus-if-needed', [WalletController::class, 'updateBonusIfNeeded']);
    Route::get('profile/wallet/transfer-balance-to-withdrawal-if-needed', [WalletController::class, 'transferBalanceToWithdrawalIfNeeded']);
    Route::get('profile/wallet/transfer-bonus-to-withdrawal-if-needed', [WalletController::class, 'transferBonusToWithdrawalIfNeeded']);
    Route::get('profile/wallet/check-and-update-bonus', [WalletController::class, 'checkAndUpdateBonus']);
    
    // Transactions
    Route::get('/transaction/status/by-token', [SuitPayController::class, 'checkTransactionStatusByToken']);
    Route::post('/pixup/qrcode', [\App\Http\Controllers\Gateway\PixupController::class, 'generateQRCode']);
    
    // Missions
    Route::get('/user-deposits', [MissionDepositController::class, 'userDeposits']);
    Route::get('/missions/check-status', [MissionDepositController::class, 'checkMissionStatus']);
    Route::post('/missions/collect/{id}', [MissionDepositController::class, 'collectMission']);

    // User Data
    Route::get('/user/cpf', function () {
        return response()->json(['cpf' => auth('api')->user()->cpf]);
    });
});

/*
|--------------------------------------------------------------------------
| SEGURANÇA E SAQUE
|--------------------------------------------------------------------------
*/
Route::post('/verify-pin', [SenSaqueController::class, 'verifyPin']);
Route::post('/sen-saque', [SenSaqueController::class, 'store']);
Route::get('/verify-senha-saque', [SenSaqueController::class, 'checkUserHasPin']);
Route::post('/account_withdraw', [AccountWithdrawController::class, 'store']);
Route::post('/profile/collect', [VipController::class, 'collect']);

/*
|--------------------------------------------------------------------------
| ESTRUTURA DE GRUPOS (JWT AUTH)
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    include_once(__DIR__ . '/groups/api/auth/auth.php');
});

Route::group(['middleware' => ['auth.jwt']], function () {
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
        
        // Language & Social
        Route::post('/getLanguage', [ProfileController::class, 'getLanguage']);
        Route::put('/updateLanguage', [ProfileController::class, 'updateLanguage']);
        Route::post('/upload-avatar', [ProfileController::class, 'uploadAvatar']);
        Route::post('/update-facebook-id', [ProfileController::class, 'updateFacebookId']);
        Route::post('/update-whatsapp-id', [ProfileController::class, 'updateWhatsappId']);
        Route::post('/update-telegram-id', [ProfileController::class, 'updateTelegramId']);
        Route::post('/update-aniversario', [ProfileController::class, 'updateAniversario']);
    });

    Route::prefix('wallet')->group(function () {
        include_once(__DIR__ . '/groups/api/wallet/deposit.php');
        include_once(__DIR__ . '/groups/api/wallet/withdraw.php');
    });

    include_once(__DIR__ . '/groups/api/missions/mission.php');
    include_once(__DIR__ . '/groups/api/missions/missionuser.php');
});
/*
|--------------------------------------------------------------------------
| GAMES & CATEGORIES
|--------------------------------------------------------------------------
*/
Route::prefix('categories')->group(function () {
    include_once(__DIR__ . '/groups/api/categories/index.php');
});

// Inclui rotas de jogos ( featured, index, etc )
include_once(__DIR__ . '/groups/api/games/index.php');

// Providers Sync
Route::prefix('providers')->group(function () {
    Route::get('/pgclone/fetch-games', [\App\Http\Controllers\Provider\PGCloneController::class, 'fetchGames']);
    Route::get('/maxapi/fetch-games', [\App\Http\Controllers\Provider\MaxApiController::class, 'fetchGames']);
});

/*
|--------------------------------------------------------------------------
| SETTINGS & SYSTEM
|--------------------------------------------------------------------------
*/
Route::prefix('settings')->group(function () {
    include_once(__DIR__ . '/groups/api/settings/settings.php');
    include_once(__DIR__ . '/groups/api/settings/banners.php');
    include_once(__DIR__ . '/groups/api/settings/currency.php');
    include_once(__DIR__ . '/groups/api/settings/bonus.php');
});

Route::prefix('search')->group(function () {
    include_once(__DIR__ . '/groups/api/search/search.php');
});

// GATEWAYS LEGADOS
include_once(__DIR__ . '/groups/api/gateways/suitpay.php');
include_once(__DIR__ . '/groups/api/gateways/bspay.php');

// LANDING SPIN
Route::prefix('spin')->group(function () {
    include_once(__DIR__ . '/groups/api/spin/index.php');
})->name('landing.spin.');