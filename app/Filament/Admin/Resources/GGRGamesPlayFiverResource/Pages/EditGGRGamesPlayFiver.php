<?php

namespace App\Filament\Admin\Resources\GGRGamesPlayFiverResource\Pages;

use App\Filament\Admin\Resources\GGRGamesPlayFiverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGGRGamesPlayFiver extends EditRecord
{
    protected static string $resource = GGRGamesPlayFiverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
