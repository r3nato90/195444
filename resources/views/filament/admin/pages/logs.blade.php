<x-filament-panels::page>
    <div class="flex flex-col gap-4">
        <div class="flex justify-end gap-2">
            <x-filament::button wire:click="refreshLogs" color="info">
                Atualizar Logs
            </x-filament::button>
            <x-filament::button wire:click="clearLogs" color="danger" onclick="return confirm('Certeza que quer apagar os logs?')">
                Limpar Logs
            </x-filament::button>
        </div>

        <div class="p-4 bg-gray-900 text-green-400 font-mono text-xs rounded-lg overflow-x-auto shadow-xl" style="max-height: 600px; border: 1px solid #333;">
            <pre class="whitespace-pre-wrap">@if($logs){{ $logs }}@else Sem logs para exibir. @endif</pre>
        </div>
    </div>
</x-filament-panels::page>