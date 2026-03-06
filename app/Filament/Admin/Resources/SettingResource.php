<?php

namespace App\Filament\Admin\Resources;

use App\Models\Setting;
use App\Filament\Admin\Resources\SettingResource\Pages;
use AymanAlhattami\FilamentPageWithSidebar\FilamentPageSidebar;
use AymanAlhattami\FilamentPageWithSidebar\PageNavigationItem;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar; // Certifique-se que este caminho existe no vendor
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SettingResource extends Resource
{
    use HasPageSidebar;

    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configurações';

    /**
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    /**
     * @param Model $record
     * @return FilamentPageSidebar
     */
    public static function sidebar(Model $record): FilamentPageSidebar
    {
        return FilamentPageSidebar::make()
            ->setTitle('Definições')
            ->setDescription('Ajustes da plataforma')
            ->setNavigationItems([
                PageNavigationItem::make('Ícones & Textos')
                    ->translateLabel()
                    ->url(static::getUrl('index'))
                    ->icon('heroicon-o-pencil-square')
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.index')),

                PageNavigationItem::make('Bônus Vip')
                    ->translateLabel()
                    ->url(static::getUrl('bonus', ['record' => $record->id]))
                    ->icon('heroicon-o-gift')
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.bonus')),

                PageNavigationItem::make('Rollover')
                    ->translateLabel()
                    ->url(static::getUrl('rollover', ['record' => $record->id]))
                    ->icon('heroicon-o-arrow-path')
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.rollover')),

                PageNavigationItem::make('Taxas')
                    ->translateLabel()
                    ->url(static::getUrl('fee', ['record' => $record->id]))
                    ->icon('heroicon-o-receipt-percent')
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.fee')),

                PageNavigationItem::make('Limites')
                    ->translateLabel()
                    ->url(static::getUrl('limit', ['record' => $record->id]))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.limit')),

                PageNavigationItem::make('Pagamentos')
                    ->translateLabel()
                    ->url(static::getUrl('payment', ['record' => $record->id]))
                    ->icon('heroicon-o-banknotes')
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.payment')),

                PageNavigationItem::make('Gateways')
                    ->translateLabel()
                    ->url(static::getUrl('gateway', ['record' => $record->id]))
                    ->icon('heroicon-o-building-library')
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.gateway')),

                PageNavigationItem::make('Afiliados')
                    ->translateLabel()
                    ->url(static::getUrl('affiliate', ['record' => $record->id]))
                    ->icon('heroicon-o-users')
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.affiliate')),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('software_name')
                    ->label('Nome da Plataforma')
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Última Atualização'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'     => Pages\DefaultSetting::route('/'),
            'limit'     => Pages\LimitSetting::route('/{record}/limit'),
            'bonus'     => Pages\BonusSetting::route('/{record}/bonus'),
            'rollover'  => Pages\RolloverSetting::route('/{record}/rollover'),
            'fee'       => Pages\FeeSetting::route('/{record}/fee'),
            'payment'   => Pages\PaymentSetting::route('/{record}/payment'),
            'affiliate' => Pages\AffiliatePage::route('/{record}/affiliate'),
            'gateway'   => Pages\GatewayPage::route('/{record}/gateway'),
        ];
    }
}