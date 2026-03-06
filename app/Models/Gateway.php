<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Gateway extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'gateways';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        // Suitpay
        'suitpay_uri',
        'suitpay_cliente_id',
        'suitpay_cliente_secret',
        'suitpay_split',
        'suitpay_split_name',

        // Stripe
        'stripe_production',
        'stripe_public_key',
        'stripe_secret_key',
        'stripe_webhook_key',

        // digitopay
        'digitopay_uri',
        'digitopay_cliente_id',
        'digitopay_cliente_secret',

        // ezzebank
        'ezzebank_uri',
        'ezzebank_cliente_id',
        'ezzebank_cliente_secret',
        'ezze_user',
        'ezze_senha',
        
        // PixUP
        'pixup_client_id',
        'pixup_client_secret',

        // bspay
        'bspay_uri',
        'bspay_cliente_id',
        'bspay_cliente_secret',

    ];

    protected $hidden = array('updated_at');

    /**
     * Mascara o ID do cliente DigitoPay em modo Demo.
     */
    protected function digitopayClienteId(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Mascara o Secret do cliente DigitoPay em modo Demo.
     */
    protected function digitopayClienteSecret(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Mascara a Public Key genérica em modo Demo.
     */
    protected function publicKey(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Mascara a Private Key genérica em modo Demo.
     */
    protected function privateKey(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Mascara o ID do cliente SuitPay em modo Demo.
     */
    protected function suitpayClienteId(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Mascara o Secret do cliente SuitPay em modo Demo.
     */
    protected function suitpayClienteSecret(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Mascara a Public Key da Stripe em modo Demo.
     */
    protected function stripePublicKey(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Mascara a Secret Key da Stripe em modo Demo.
     */
    protected function stripeSecretKey(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Mascara o ID do cliente PixUP em modo Demo.
     */
    protected function pixupClientId(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /**
     * Mascara o Secret do cliente PixUP em modo Demo.
     */
    protected function pixupClientSecret(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }
}