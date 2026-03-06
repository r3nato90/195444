<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <div class="flex justify-end gap-x-3">
            <x-filament::button wire:click="refreshLogs" color="gray" icon="heroicon-m-arrow-path">
                Atualizar agora
            </x-filament::button>

            <x-filament::button wire:click="clearLogs" color="danger" icon="heroicon-m-trash" onclick="return confirm('Deseja realmente apagar todo o histórico de logs?')">
                Limpar arquivo
            </x-filament::button>
        </div>

        <div class="rounded-xl border border-gray-700 bg-gray-950 p-6 shadow-sm overflow-hidden">
            <div class="overflow-x-auto max-h-[600px]">
                <pre class="font-mono text-xs leading-relaxed text-green-500 whitespace-pre-wrap">@if($logs){{ $logs }}@else Arquivo de log vazio ou não criado. @endif</pre>
            </div>
        </div>
    </div>
</x-filament-panels::page>