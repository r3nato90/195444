<?php

namespace App\Filament\Admin\Resources\GGRGamesPlayFiverResource\Pages;

use App\Filament\Admin\Resources\GGRGamesPlayFiverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGGRGamesPlayFiver extends ListRecords
{
    protected static string $resource = GGRGamesPlayFiverResource::class; 

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
