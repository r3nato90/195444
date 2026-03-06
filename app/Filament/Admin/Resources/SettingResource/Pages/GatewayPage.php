<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use App\Models\Setting;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use App\Models\AproveSaveSetting;

class GatewayPage extends Page implements HasForms
{
    use HasPageSidebar, InteractsWithForms;

    protected static string $resource = SettingResource::class;

    protected static string $view = 'filament.resources.setting-resource.pages.gateway-page';

    public function getTitle(): string|Htmlable
    {
        return 'Gateways';
    }

    public Setting $record;
    public ?array $data = [];

    public static function canView(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function mount(): void
    {
        $setting = Setting::first();
        if ($setting) {
            $this->record = $setting;
            $this->form->fill($setting->toArray());
        } else {
            $this->form->fill();
        }
    }

    public function save()
    {
        try {
            if (env('APP_DEMO')) {
                Notification::make()->title('Atenção')->body('Você não pode realizar esta alteração na versão demo')->danger()->send();
                return;
            }

            $approvalSettings = AproveSaveSetting::first();
            $inputPassword = $this->data['approval_password_save'] ?? '';

            if (!$approvalSettings || !Hash::check($inputPassword, $approvalSettings->approval_password_save)) {
                Notification::make()->title('Erro de Autenticação')->body('Senha incorreta. Por favor, tente novamente.')->danger()->send();
                return;
            }

            $setting = Setting::find($this->record->id ?? 1);
            
            // Remove a senha do array antes de tentar salvar no banco de dados
            $dadosParaSalvar = Arr::except($this->data, ['approval_password_save']);

            if ($setting) {
                if ($setting->update($dadosParaSalvar)) {
                    Cache::put('setting', $setting);
                    Notification::make()->title('Sucesso')->body('Dados do gateway alterados com sucesso!')->success()->send();
                }
            }
        } catch (Halt $exception) {
            Notification::make()->title('Erro')->body('Ocorreu um problema ao salvar.')->danger()->send();
            return;
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Gateways')
                    ->description('Ative ou desative seus gateways de Pagamento')
                    ->schema([
                        Select::make('default_gateway')
                            ->label('Gateway Padrão para Saque e Depósito')
                            ->options([
                                'suitpay' => 'Suitpay (Ecompag)',
                                'pixup'   => 'PixUP (v2)', // Adicionado a Pixup como opção visível
                            ])->columnSpanFull(),
                            
                        Toggle::make('suitpay_is_enable')
                            ->label('Suitpay Ativo'),

                        // ADICIONADO: Toggle da PixUP
                        Toggle::make('pixup_is_enable')
                            ->label('PixUP Ativo'),
                            
                    ])->columns(2), // O columnSpanFull no Select e columns 2 aqui deixa os dois Toggles alinhados perfeitamente lado a lado
                    
                Section::make('Digite a senha de confirmação')
                    ->description('Obrigatório digitar sua senha de confirmação para efetuar mudanças de segurança!')
                    ->schema([
                        TextInput::make('approval_password_save')
                            ->label('Senha de Aprovação')
                            ->password()
                            ->required()
                            ->helperText('Digite a senha para salvar as alterações.')
                            ->maxLength(191),
                    ])->columns(1)
            ])
            ->statePath('data');
    }
}