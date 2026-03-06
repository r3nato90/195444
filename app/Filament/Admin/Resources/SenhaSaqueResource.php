<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SenhaSaqueResource\Pages;
use App\Models\SenSaque;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Models\AproveSaveSetting;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\TextInput;

class SenhaSaqueResource extends Resource
{
    protected static ?string $model = SenSaque::class;

    protected static ?string $navigationIcon = 'heroicon-o-key'; 
    protected static ?string $navigationLabel = 'Senhas de Saques Usuarios'; 
    protected static ?string $modelLabel = 'Senha de Saque Usuarios'; 
    protected static ?string $slug = 'senha-saques'; 

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * O modal de visualização/edição usa este método.
     * REMOVIDO o ->copyable() daqui para corrigir o erro.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes do PIN de Saque')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('User ID')
                            ->disabled(),

                        // CORREÇÃO: TextInput não aceita o método copyable()
                        Forms\Components\TextInput::make('valid_saque')
                            ->label('Senha / PIN de Saque')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Data de Criação')
                            ->disabled(),
                    ])->columns(2)
            ]);
    }

    /**
     * A listagem principal (colunas) usa este método.
     * Aqui o copyable() funciona perfeitamente.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->label('ID Usuário')
                    ->searchable()
                    ->sortable(),

                // NA TABELA (TextColumn) o copyable() é permitido e funcional
                Tables\Columns\TextColumn::make('valid_saque')
                    ->label('Senha de Saque (PIN)')
                    ->weight('bold')
                    ->color('warning')
                    ->copyable() 
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->form([
                        TextInput::make('approval_password_bulk_delete')
                            ->password()
                            ->required()
                            ->label('Senha Administrativa')
                    ])
                    ->action(function ($records, array $data) {
                        $approvalSettings = AproveSaveSetting::first();
                        if (!Hash::check($data['approval_password_bulk_delete'], $approvalSettings->approval_password_save)) {
                            Notification::make()->title('Acesso Negado')->danger()->send();
                            return;
                        }
                        foreach ($records as $record) { $record->delete(); }
                        Notification::make()->title('Registros removidos')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSenhaSaques::route('/'),
        ];
    }
}