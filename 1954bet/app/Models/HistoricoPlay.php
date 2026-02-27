<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoPlay extends Model
{
    protected $table = 'historico_play';
    public $timestamps = false; 
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}