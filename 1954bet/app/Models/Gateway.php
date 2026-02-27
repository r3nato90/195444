<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Gateway extends Model
{
    use HasFactory;

    protected $table = 'gateways';

    protected $fillable = [
        'suitpay_uri',
        'suitpay_cliente_id',
        'suitpay_cliente_secret',
        'suitpay_is_enable', 

        'bspay_uri',
        'bspay_cliente_id',
        'bspay_cliente_secret',
        'bspay_is_enable',   // Usado para WishPag

        'digitopay_uri',
        'digitopay_cliente_id',
        'digitopay_cliente_secret',
        'digitopay_is_enable', // Usado para Pixup

        'ezzebank_uri',
        'ezzebank_cliente_id',
        'ezzebank_cliente_secret',
        'ezzebank_is_enable', // Usado para Versell Pay
        'ezze_user',
        'ezze_senha',

        'stripe_production',
        'stripe_public_key',
        'stripe_secret_key',
        'stripe_webhook_key',

        'default_gateway',   
    ];

    protected $hidden = array('updated_at');

    /**
     * Mapeamento virtual: WishPag (usa coluna bspay)
     */
    protected function wishpagSecretKey(): Attribute
    {
        return Attribute::make(get: fn() => env('APP_DEMO') ? '********' : $this->bspay_cliente_id);
    }

    /**
     * Mapeamento virtual: Pixup (usa coluna digitopay)
     */
    protected function pixupClientId(): Attribute
    {
        return Attribute::make(get: fn() => env('APP_DEMO') ? '********' : $this->digitopay_cliente_id);
    }
    
    protected function pixupClientSecret(): Attribute
    {
        return Attribute::make(get: fn() => env('APP_DEMO') ? '********' : $this->digitopay_cliente_secret);
    }

    /**
     * Mapeamento virtual: Versell Pay (usa coluna ezzebank)
     */
    protected function versellClientId(): Attribute
    {
        return Attribute::make(get: fn() => env('APP_DEMO') ? '********' : $this->ezzebank_cliente_id);
    }

    protected function versellClientSecret(): Attribute
    {
        return Attribute::make(get: fn() => env('APP_DEMO') ? '********' : $this->ezzebank_cliente_secret);
    }
}