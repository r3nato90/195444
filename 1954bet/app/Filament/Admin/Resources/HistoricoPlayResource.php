<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HistoricoPlayResource\Pages;
use App\Models\HistoricoPlay;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class HistoricoPlayResource extends Resource
{
    protected static ?string $model = HistoricoPlay::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Gestão PGClone';
    protected static ?string $navigationLabel = 'Histórico de Apostas';
    protected static ?string $pluralModelLabel = 'Histórico de Apostas';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Jogador')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nome_game')
                    ->label('Jogo')
                    ->searchable()
                    ->badge(),

                TextColumn::make('bet_money')
                    ->label('Apostou')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('win_money')
                    ->label('Ganhou')
                    ->money('BRL')
                    ->sortable()
                    ->color(fn ($record) => $record->win_money > 0 ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('txn_id')
                    ->label('ID da Transação')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Data / Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHistoricoPlays::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}