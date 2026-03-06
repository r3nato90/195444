<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SenSaque;

class ClearWithdrawalPasswords extends Command
{
    protected $signature = 'withdrawal:clear-pins';
    protected $description = 'Remove todos os PINs de saque antigos (Hashed) para recadastro em texto puro';

    public function handle()
    {
        if ($this->confirm('Isso apagará TODAS as senhas de saque. Deseja continuar?')) {
            SenSaque::truncate();
            $this->info('Tabela de senhas limpa com sucesso!');
        }
    }
}