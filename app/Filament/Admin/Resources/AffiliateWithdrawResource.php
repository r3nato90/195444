<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Resources\AffiliateWithdrawResource\Pages;
use App\Models\AffiliateWithdraw;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class AffiliateWithdrawResource extends Resource
{
    protected static ?string $model = AffiliateWithdraw::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    /**
     * @dev @anonymous
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('afiliado') || auth()->user()->hasRole('admin');
    }

    /**
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return auth()->user()->hasRole('afiliado') ? 'Meus Saques' : 'Saques de Afiliados';
    }

    /**
     * @return string
     */
    public static function getModelLabel(): string
    {
        return auth()->user()->hasRole('afiliado') ? 'Meus Saques' : 'Saques de Afiliados';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    /**
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->query(auth()->user()->hasRole('afiliado') ? AffiliateWithdraw::query()->where('user_id', auth()->id()) : AffiliateWithdraw::query())
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('E-mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn(AffiliateWithdraw $record): string => $record->symbol . ' ' . $record->amount)
                    ->sortable(),
                Tables\Columns\TextColumn::make('pix_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn(string $state): string => \Helper::formatPixType($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('pix_key')
                    ->label('Chave Pix'),
                Tables\Columns\TextColumn::make('bank_info')
                    ->label('Informações Bancaria')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('proof')
                    ->label('Comprovante')
                    ->html()
                    ->formatStateUsing(fn(string $state): string => '<a href="' . url('storage/' . $state) . '" target="_blank">Baixar</a>')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('deny_payment')
                    ->label('Cancelar')
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->visible(fn(AffiliateWithdraw $withdrawal): bool => !$withdrawal->status)
                    ->action(function (AffiliateWithdraw $withdrawal) {
                        \Filament\Notifications\Notification::make()
                            ->title('Cancelar Saque')
                            ->success()
                            ->persistent()
                            ->body('Você está cancelando saque de ' . \Helper::amountFormatDecimal($withdrawal->amount))
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Confirmar')
                                    ->button()
                                    ->url(route(\Helper::GetDefaultGateway() . '.cancelwithdrawal', ['id' => $withdrawal->id, 'action' => 'affiliate']))
                                    ->close(),
                                \Filament\Notifications\Actions\Action::make('undo')
                                    ->color('gray')
                                    ->label('Cancelar')
                                    ->action(function (AffiliateWithdraw $withdrawal) {

                                    })
                                    ->close(),
                            ])
                            ->send();
                    }),
                Action::make('approve_payment')
                    ->label('Fazer pagamento')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(AffiliateWithdraw $withdrawal): bool => !$withdrawal->status)
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Digite a senha de aprovação')
                            ->password()
                            ->required(),
                    ])
                    ->action(function (AffiliateWithdraw $withdrawal, array $data) {
                        $withdrawalPassword = \DB::table('aprove_withdrawal')->value('approval_password');
                        $lastRequestedAt = \DB::table('aprove_withdrawal')->value('last_requested_at');
                        $currentTimestamp = now();

                        // Verifique se o tempo desde a última solicitação é maior que 5 minutos
                        if ($lastRequestedAt && $currentTimestamp->diffInMinutes($lastRequestedAt) < 5) {
                            \Filament\Notifications\Notification::make()
                                ->title('Ação Restrita')
                                ->danger()
                                ->body('Você só pode solicitar a senha de aprovação uma vez a cada 5 minutos.')
                                ->send();
                            return;
                        }

                        // Verifique se a senha está correta
                        if (Hash::check($data['password'], $withdrawalPassword)) {
                            // Atualize o timestamp da última solicitação
                            \DB::table('aprove_withdrawal')->update(['last_requested_at' => $currentTimestamp]);

                            \Filament\Notifications\Notification::make()
                                ->title('Saque')
                                ->success()
                                ->persistent()
                                ->body('Você está solicitando um saque de ' . \Helper::amountFormatDecimal($withdrawal->amount))
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('Confirmar')
                                        ->button()
                                        ->url(route(\Helper::GetDefaultGateway() . '.withdrawal', ['id' => $withdrawal->id, 'action' => 'affiliate']))
                                        ->close(),
                                    \Filament\Notifications\Actions\Action::make('undo')
                                        ->color('gray')
                                        ->label('Cancelar')
                                        ->action(function (AffiliateWithdraw $withdrawal) {
                                            // Lógica para cancelar se necessário
                                        })
                                        ->close(),
                                ])
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Senha Incorreta')
                                ->danger()
                                ->body('A senha de aprovação está incorreta. O saque não foi aprovado.')
                                ->send();
                        }
                    })

                //Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\AffiliateWithdrawResource\Pages\ListAffiliateWithdraws::route('/'),
        ];
    }
}
