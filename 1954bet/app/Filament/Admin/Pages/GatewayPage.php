<?php

namespace App\Filament\Admin\Pages;

use App\Models\Gateway;
use App\Models\AproveSaveSetting;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class GatewayPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static string $view = 'filament.pages.gateway-page';
    protected static ?string $title = 'Credenciais de Gateways';
    public ?array $data = [];

    public function mount(): void
    {
        $gateway = Gateway::first();
        if ($gateway) { $this->form->fill($gateway->toArray()); }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Credenciais de API')
                    ->tabs([
                        Tabs\Tab::make('WishPag')
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                TextInput::make('bspay_uri')->label('URL API')->default('https://api.wishpag.com/v1'),
                                TextInput::make('bspay_cliente_id')->label('Secret Key')->password()->required(),
                                TextInput::make('bspay_cliente_secret')->label('Company ID')->required(),
                                
                                Section::make('URLs de Webhook (Copie e cole no painel da WishPag)')
                                    ->schema([
                                        TextInput::make('wishpag_webhook_in')
                                            ->label('Webhook de Depósitos (Cash-in)')
                                            ->formatStateUsing(fn () => url('/api/webhooks/wishpag/cashin'))
                                            ->readOnly()
                                            ->dehydrated(false),
                                            
                                        TextInput::make('wishpag_webhook_out')
                                            ->label('Webhook de Saques (Cash-out)')
                                            ->formatStateUsing(fn () => url('/api/webhooks/wishpag/cashout'))
                                            ->readOnly()
                                            ->dehydrated(false),
                                    ])->columns(2),
                            ])->columns(2),

                        Tabs\Tab::make('Pixup')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                TextInput::make('digitopay_uri')->label('URL API')->default('https://api.pixup.com.br/v2'),
                                TextInput::make('digitopay_cliente_id')->label('Client ID')->required(),
                                TextInput::make('digitopay_cliente_secret')->label('Client Secret')->password()->required(),

                                Section::make('URL de Webhook (Copie e cole no painel da Pixup)')
                                    ->schema([
                                        TextInput::make('pixup_webhook_in')
                                            ->label('Webhook de Depósitos (Cash-in)')
                                            ->formatStateUsing(fn () => url('/api/webhooks/pixup/cashin'))
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpanFull(),
                                    ]),
                            ])->columns(2),

                        Tabs\Tab::make('Versell Pay')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                TextInput::make('ezzebank_uri')->label('URL API')->default('https://api.versellpay.com'),
                                TextInput::make('ezzebank_cliente_id')->label('Client ID (vspi)')->required(),
                                TextInput::make('ezzebank_cliente_secret')->label('Client Secret (vsps)')->password()->required(),

                                Section::make('URL de Webhook (Copie e cole no painel da Versell)')
                                    ->schema([
                                        TextInput::make('versell_webhook_in')
                                            ->label('Webhook de Depósitos (Cash-in)')
                                            ->formatStateUsing(fn () => url('/api/webhooks/versell/cashin'))
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpanFull(),
                                    ]),
                            ])->columns(2),

                        Tabs\Tab::make('SuitPay')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                TextInput::make('suitpay_uri')->label('URL API'),
                                TextInput::make('suitpay_cliente_id')->label('Client ID'),
                                TextInput::make('suitpay_cliente_secret')->label('Client Secret')->password(),

                                Section::make('URL de Webhook (Copie e cole no painel da SuitPay)')
                                    ->schema([
                                        TextInput::make('suitpay_webhook_in')
                                            ->label('Webhook de Callback')
                                            ->formatStateUsing(fn () => url('/api/gateways/suitpay/callback'))
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpanFull(),
                                    ]),
                            ])->columns(2),
                    ])->columnSpanFull(),

                Section::make('Segurança')
                    ->schema([
                        TextInput::make('approval_password_save')->label('Senha Admin')->password()->required(),
                    ]),
            ])->statePath('data');
    }

    public function submit(): void
    {
        if (env('APP_DEMO')) {
            Notification::make()->title('Atenção')->danger()->send(); return;
        }

        $approval = AproveSaveSetting::first();
        if (!$approval || !Hash::check($this->data['approval_password_save'], $approval->approval_password_save)) {
            Notification::make()->title('Senha Incorreta')->danger()->send(); return;
        }
        $updateData = $this->data;
        unset($updateData['approval_password_save']);
        
        $gateway = Gateway::first() ?? new Gateway();
        $gateway->fill($updateData)->save();
        
        Notification::make()->title('Chaves Guardadas')->success()->send();
    }
}