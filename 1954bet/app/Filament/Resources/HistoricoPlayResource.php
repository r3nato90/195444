<?php

namespace App\Filament\Resources;

use App\Models\HistoricoPlay;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class HistoricoPlayResource extends Resource
{
    protected static ?string $model = HistoricoPlay::class;

    // Ícone que vai aparecer no menu lateral
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    // Grupo do menu
    protected static ?string $navigationGroup = 'Gestão PGClone';
    
    // Nome no menu
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
                    ->badge(), // Dá um visual de "etiqueta" ao nome do jogo

                TextColumn::make('bet_money')
                    ->label('Apostou')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('win_money')
                    ->label('Ganhou')
                    ->money('BRL')
                    ->sortable()
                    ->color(fn ($record) => $record->win_money > 0 ? 'success' : 'danger') // Fica verde se ganhou, vermelho se perdeu
                    ->weight('bold'),

                TextColumn::make('txn_id')
                    ->label('ID da Transação')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Esconde por padrão para não poluir a tela

                TextColumn::make('created_at')
                    ->label('Data / Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc') // Mostra sempre a última aposta no topo
            ->filters([
                // Aqui podemos adicionar filtros por data depois
            ])
            ->actions([
                // Sem ações de edição, pois histórico não deve ser alterado
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Filament\Resources\Pages\ListRecords::route('/'),
        ];
    }

    // Bloqueia o botão de "Criar" novo histórico manualmente (medida de segurança e auditoria)
    public static function canCreate(): bool
    {
        return false;
    }
}