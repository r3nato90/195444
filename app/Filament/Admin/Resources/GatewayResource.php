<?php

namespace App\Filament\Admin\Resources;

use App\Models\Gateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class GatewayResource extends Resource
{
    protected static ?string $model = Gateway::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Chaves das APIs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('WishPag - Credenciais')
                    ->schema([
                        TextInput::make('wishpag_secret_key')->label('Secret Key')->password()->revealable(),
                        TextInput::make('wishpag_company_id')->label('Company ID'),
                    ])->columns(2),

                Section::make('Versell Pay - Credenciais')
                    ->schema([
                        TextInput::make('versell_client_id')->label('Client ID (vspi)'),
                        TextInput::make('versell_client_secret')->label('Client Secret (vsps)')->password()->revealable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('updated_at')->label('Última Alteração')->dateTime(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => GatewayResource\Pages\ListGateways::route('/'),
            'edit' => GatewayResource\Pages\EditGateway::route('/{record}/edit'),
        ];
    }
}