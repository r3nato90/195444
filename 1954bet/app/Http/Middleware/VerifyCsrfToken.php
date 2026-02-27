<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',
        'digitopay/*',
        'suitpay/*',
        'bspay/*',
        'vgames/*',
        'webhooks/*',
        'callback',
		'callback/*',
        'gold_api',
        'gold_api/*',
        'wizzepro/gold_api/user_balance',
        'wizzepro/gold_api/game_callback',
        'cron/*',
        'playfiver/*',
        'api/webhooks/wishpag/cashin',
        'api/webhooks/wishpag/cashout',
        'api/webhooks/wishpag/*',
        'api/webhooks/versell/*',
        'api/webhooks/pixup/*',
        'api/webhooks/pgclone/*',
        'api/webhooks/maxapi/*',
        'api/pixup/webhook', // Libera o webhook para receber dados externos
    ];
}
