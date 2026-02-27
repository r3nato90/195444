<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GamesKey extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'games_keys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // API PRAGMATIC 40 Jogos
        'apipragmatic40_url',
        'apipragmatic40_secret',
        'apipragmatic40_code',
        'apipragmatic40_token',
        
        // Games2 Api
        'games2_agent_code',
        'games2_agent_token',
        'games2_agent_secret_key',
        'games2_api_endpoint',

        // wizze pro
        'wizze_agent_code',
        'wizze_agent_token',
        'wizze_agent_secret',
        'wizze_api_endpoint',

        // PlayFiver
        'playfiver_url',
        'playfiver_rtp',
        'playfiver_secret',
        'playfiver_code',
        'playfiver_token',
        'playfiver_is_enable', // <-- Novo campo de ativação

        // Colunas usadas para PGClone (mapeadas)
        'play_gaming_base_url',
        'play_gaming_agent_code',
        'play_gaming_agent_token',
        'play_gaming_agent_secret',
        'pgclone_is_enable', // <-- Novo campo de ativação

        // Colunas usadas para MAX API GAMES (mapeadas)
        'evergame_base_url',
        'evergame_agent_code',
        'evergame_agent_token',
        'evergame_agent_secret',
        'maxapi_is_enable', // <-- Novo campo de ativação
    ];

    protected $hidden = array('updated_at');

    /**
     * ==========================================
     * MAPEAMENTO VIRTUAL: PGCLONE (Usa play_gaming)
     * ==========================================
     */
    protected function pgcloneBaseUrl(): Attribute
    {
        return Attribute::make(get: fn() => $this->play_gaming_base_url ?? 'https://api.pgclone.com');
    }

    protected function pgcloneAgentCode(): Attribute
    {
        return Attribute::make(get: fn() => env('APP_DEMO') ? '*********************' : $this->play_gaming_agent_code);
    }

    protected function pgcloneAgentToken(): Attribute
    {
        return Attribute::make(get: fn() => env('APP_DEMO') ? '*********************' : $this->play_gaming_agent_token);
    }

    protected function pgcloneAgentSecret(): Attribute
    {
        return Attribute::make(get: fn() => env('APP_DEMO') ? '*********************' : $this->play_gaming_agent_secret);
    }

    /**
     * ==========================================
     * MAPEAMENTO VIRTUAL: MAX API GAMES (Usa evergame)
     * ==========================================
     */
    protected function maxapiBaseUrl(): Attribute
    {
        return Attribute::make(get: fn() => $this->evergame_base_url ?? 'https://maxapigames.com/api/v2');
    }

    protected function maxapiAgentSecret(): Attribute
    {
        return Attribute::make(get: fn() => env('APP_DEMO') ? '*********************' : $this->evergame_agent_secret);
    }


    /**
     * ==========================================
     * ACCESSORS ORIGINAIS MANTIDOS
     * ==========================================
     */
    protected function pigAgentCode(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function pigAgentToken(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function pigAgentSecret(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function drakonAgentCode(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function drakonAgentToken(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function drakonAgentSecret(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function merchantId(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function merchantKey(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function worldslotAgentCode(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function worldslotAgentToken(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function worldslotAgentSecretKey(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function evergameAgentCode(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
    protected function evergameAgentToken(): Attribute { return Attribute::make(get: fn(?string $value) => env('APP_DEMO') ? '*********************' : $value); }
}