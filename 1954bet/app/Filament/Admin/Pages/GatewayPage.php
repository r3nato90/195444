<?php

namespace App\Filament\Admin\Pages;

use App\Models\Gateway;
use App\Models\AproveSaveSetting; // Importação do modelo para verificação de senha
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder; // Importação adicionada
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Hash; // Importação para verificação da senha
use Illuminate\Support\HtmlString; // Importação adicionada
// use Jackiedo\DotenvEditor\Facades\DotenvEditor; // Removido, pois não é mais necessário para Stripe

class GatewayPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.gateway-page';

    public ?array $data = [];
    public Gateway $setting;

    /**
     * @dev @anonymous
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        $gateway = Gateway::first();
        if (!empty($gateway)) {
            $this->setting = $gateway;
            $this->form->fill($this->setting->toArray());
        } else {
            $this->form->fill();
        }
    }

    /**
     * @param Form $form
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Bspay Br')
                    ->schema([
                        Placeholder::make('bspay_info')
                            ->label('') // Deixado vazio para o conteúdo HTML
                            ->content(new HtmlString('
                                <div style="display: flex; align-items: center; flex-wrap: wrap;">
                                    ABRA SUA CONTA TAXA DE 97 NA ABERTURA DA CONTA ABRA DIRETO COM O GERENTE :
                                    <a class="dark:text-white"
                                        style="
                                            font-size: 14px;
                                            font-weight: 600;
                                            width: 127px;
                                            display: flex;
                                            background-color: #f800ff;
                                            padding: 10px;
                                            border-radius: 11px;
                                            justify-content: center;
                                            margin-left: 10px;
                                            margin-top: 5px;
                                        "
                                        href="https://bspaybr.com/login"
                                        target="_blank">
                                        BspayBR Painel
                                    </a>
                                    <a class="dark:text-white"
                                        style="
                                            font-size: 14px;
                                            font-weight: 600;
                                            width: 127px;
                                            display: flex;
                                            background-color: #f800ff;
                                            padding: 10px;
                                            border-radius: 11px;
                                            justify-content: center;
                                            margin-left: 10px;
                                            margin-top: 5px;
                                        "
                                        href="https://wa.me/+5546999247838"
                                        target="_blank">
                                        Gerente Bspay BR
                                    </a>
                                </div>
                            ')),
                        TextInput::make('suitpay_uri')
                            ->label('Client URI')
                            ->placeholder('Digite a url da api')
                            ->maxLength(191),
                        TextInput::make('suitpay_cliente_id')
                            ->label('Client ID')
                            ->placeholder('Digite o client ID')
                            ->maxLength(191),
                        TextInput::make('suitpay_cliente_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o client secret')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        // Campos Stripe removidos
                    ])->columns(2),

                // Seção para a senha de aprovação
                Section::make('Digite a senha de confirmação')
                    ->description('Obrigatório digitar sua senha de confirmação!')
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


    /**
     * @return void
     */
    public function submit(): void
    {
        try {
            if (env('APP_DEMO')) {
                Notification::make()
                    ->title('Atenção')
                    ->body('Você não pode realizar está alteração na versão demo')
                    ->danger()
                    ->send();
                return;
            }

            // Verificação da senha
            $approvalSettings = AproveSaveSetting::first();
            $inputPassword = $this->data['approval_password_save'] ?? '';

            if (!$approvalSettings || !Hash::check($inputPassword, $approvalSettings->approval_password_save)) {
                Notification::make()
                    ->title('Erro de Autenticação')
                    ->body('Senha incorreta. Por favor, tente novamente.')
                    ->danger()
                    ->send();
                return;
            }

            $setting = Gateway::first();
            if (!empty($setting)) {
                if ($setting->update($this->data)) {
                    // Lógica de atualização do .env para Stripe removida

                    Notification::make()
                        ->title('Chaves Alteradas')
                        ->body('Suas chaves foram alteradas com sucesso!')
                        ->success()
                        ->send();
                }
            } else {
                if (Gateway::create($this->data)) {
                    // Lógica de criação e atualização do .env para Stripe removida

                    Notification::make()
                        ->title('Chaves Criadas')
                        ->body('Suas chaves foram criadas com sucesso!')
                        ->success()
                        ->send();
                }
            }

        } catch (Halt $exception) {
            Notification::make()
                ->title('Erro ao alterar dados!')
                ->body('Erro ao alterar dados!')
                ->danger()
                ->send();
        }
    }
}
