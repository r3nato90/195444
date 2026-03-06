<?php

namespace App\Filament\Admin\Pages;

use App\Models\Gateway;
use App\Models\AproveSaveSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Arr;

class GatewayPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.gateway-page';

    public ?array $data = [];
    public ?Gateway $setting = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function mount(): void
    {
        $gateway = Gateway::first();
        if ($gateway) {
            $this->setting = $gateway;
            $this->form->fill($this->setting->toArray());
        } else {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Ecompaga / Suitpay')
                    ->description(new HtmlString('<div style="display: flex; align-items: center;">Precisa de uma conta Ecompag? Registre-se agora mesmo aprovação instantânea sem verificação de documentos:
                            <a class="dark:text-white" style="font-size: 14px;font-weight: 600;width: 127px;display: flex;background-color: #00b54d;padding: 10px;border-radius: 11px;justify-content: center;margin-left: 10px;" href="https://ecompag.com" target="_blank">Dashboard</a>
                            <a class="dark:text-white" style="font-size: 14px;font-weight: 600;width: 127px;display: flex;background-color: #00b54d;padding: 10px;border-radius: 11px;justify-content: center;margin-left: 10px;" href="https://chat.whatsapp.com/KJfzzoFs4U35XFvsL9hO2M" target="_blank">Comunidade Ecompag</a>
                        </div>'))
                    ->schema([
                        TextInput::make('suitpay_uri')
                            ->label('Client URI')
                            ->placeholder('Digite a URL da API')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('suitpay_cliente_id')
                            ->label('Client ID')
                            ->placeholder('Digite o Client ID')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('suitpay_cliente_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o Client Secret')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('callback_url')
                            ->label('Callback URL')
                            ->placeholder('Digite a URL de callback')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ]),
                
                // --- SEÇÃO PIXUP ---
                Section::make('PixUP')
                    ->description('Configurações da API de Pagamentos PixUP')
                    ->schema([
                        TextInput::make('pixup_client_id')
                            ->label('Client ID')
                            ->placeholder('Ex: luanbcsc_03484...')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('pixup_client_secret')
                            ->label('Token Secreto')
                            ->placeholder('Ex: 9fd0756230...')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ]),

                // --- SEÇÃO DE SEGURANÇA ---
                Section::make('Confirmação de Segurança')
                    ->description('Obrigatório digitar sua senha de aprovação para salvar as chaves!')
                    ->schema([
                        TextInput::make('approval_password_save')
                            ->label('Senha de Aprovação')
                            ->password()
                            ->required()
                            ->helperText('Digite a senha para salvar as alterações.')
                            ->maxLength(191),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        try {
            if (env('APP_DEMO')) {
                Notification::make()
                    ->title('Atenção')
                    ->body('Você não pode realizar esta alteração na versão demo')
                    ->danger()
                    ->send();
                return;
            }

            $approvalSettings = AproveSaveSetting::first();
            $inputPassword = $this->data['approval_password_save'] ?? '';

            if (!$approvalSettings || !Hash::check($inputPassword, $approvalSettings->approval_password_save)) {
                Notification::make()
                    ->title('Erro de Autenticação')
                    ->body('Senha de aprovação incorreta. Por favor, tente novamente.')
                    ->danger()
                    ->send();
                return;
            }

            $setting = Gateway::first();
            
            // Remove a senha do array de dados para não quebrar a inserção no banco
            $dadosParaSalvar = Arr::except($this->data, ['approval_password_save']);

            if ($setting) {
                $setting->update($dadosParaSalvar);

                Notification::make()
                    ->title('Chaves Alteradas')
                    ->body('Suas chaves foram alteradas com sucesso!')
                    ->success()
                    ->send();
            } else {
                Gateway::create($dadosParaSalvar);

                Notification::make()
                    ->title('Chaves Criadas')
                    ->body('Suas chaves foram criadas com sucesso!')
                    ->success()
                    ->send();
            }
        } catch (Halt $exception) {
            Notification::make()
                ->title('Erro ao alterar dados!')
                ->body('Ocorreu um erro ao tentar salvar as informações.')
                ->danger()
                ->send();
        }
    }
}