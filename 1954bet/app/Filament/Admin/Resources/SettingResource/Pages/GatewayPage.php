<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use App\Models\Gateway;
use App\Models\AproveSaveSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Hash;

class GatewayPage extends Page
{
    use InteractsWithForms;

    protected static string $resource = SettingResource::class;
    protected static ?string $title = 'Ativação de Gateways';
    protected static string $view = 'filament.pages.gateway-page';

    public ?array $data = [];
    public $record;

    public function mount($record = null): void
    {
        $this->record = $record;
        $gateway = Gateway::first();
        if ($gateway) {
            $this->form->fill($gateway->toArray());
        } else {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuração de Fluxo de Pagamentos')
                    ->description('Ative os gateways para depósito e defina o padrão para saques.')
                    ->schema([
                        Select::make('default_gateway')
                            ->label('Gateway Padrão para Saque')
                            ->options([
                                'suitpay'   => 'Suitpay',
                                'bspay'     => 'WishPag',
                                'digitopay' => 'Pixup',
                                'ezzebank'  => 'Versell Pay',
                            ])
                            ->native(false)
                            ->required()
                            ->columnSpanFull(),

                        Toggle::make('suitpay_is_enable')->label('SuitPay Ativo para Depósito')->inline(false),
                        Toggle::make('bspay_is_enable')->label('WishPag Ativo para Depósito')->inline(false),
                        Toggle::make('digitopay_is_enable')->label('Pixup Ativo para Depósito')->inline(false),
                        Toggle::make('ezzebank_is_enable')->label('Versell Pay Ativo para Depósito')->inline(false),
                    ])->columns(4),

                Section::make('Confirmação')
                    ->schema([
                        TextInput::make('approval_password_save')
                            ->label('Senha de Confirmação')
                            ->password()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        if (env('APP_DEMO')) {
            Notification::make()->title('Atenção')->body('Ação não permitida em modo Demo.')->danger()->send(); return;
        }

        $approval = AproveSaveSetting::first();
        if (!$approval || !Hash::check($this->data['approval_password_save'], $approval->approval_password_save)) {
            Notification::make()->title('Senha Incorreta')->danger()->send(); return;
        }

        $updateData = $this->data;
        unset($updateData['approval_password_save']);

        $gateway = Gateway::first() ?? new Gateway();
        $gateway->fill($updateData)->save();

        Notification::make()->title('Sucesso')->body('Ativações atualizadas!')->success()->send();
    }
}