<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Filament\Actions\Action;

class Logs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Logs do Sistema';
    protected static ?string $title = 'Logs de Pagamento (Sequestro)';
    protected static ?string $navigationGroup = 'Configurações';

    // Ajustado para o caminho que você sugeriu
    protected static string $view = 'filament.pages.logs';

    public $logs = '';

    public function mount()
    {
        $this->refreshLogs();
    }

    public function refreshLogs()
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            $file = file($logPath);
            // Pega as últimas 100 linhas para o painel não ficar pesado
            $lastLines = array_slice($file, -100);
            $this->logs = implode("", $lastLines);
        } else {
            $this->logs = 'Nenhum log encontrado no servidor.';
        }
    }

    public function clearLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        File::put($logPath, '');
        $this->refreshLogs();
    }
}