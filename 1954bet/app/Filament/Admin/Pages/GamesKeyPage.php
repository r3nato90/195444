<?php

namespace App\Filament\Admin\Pages;

use App\Models\GamesKey;
use App\Models\AproveSaveSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Hash;
use Filament\Actions\Action;

class GamesKeyPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key'; // Ícone alterado para combinar com chaves
    protected static string $view = 'filament.pages.games-key-page';
    protected static ?string $title = 'Chaves dos Provedores';
    protected static ?string $navigationGroup = 'Configurações';

    public ?array $data = [];
    public ?GamesKey $setting;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function mount(): void
    {
        $gamesKey = GamesKey::first();
        if (!empty($gamesKey)) {
            $this->setting = $gamesKey;
            $this->form->fill($this->setting->toArray());
        } else {
            $this->form->fill();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // AÇÃO DE SINCRONIZAÇÃO PGCLONE
            Action::make('sync_pgclone')
                ->label('Sincronizar PG Soft (PGClone)')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Sincronizar Jogos?')
                ->modalDescription('Isso irá atualizar o catálogo, baixar as novas imagens e limpar registros antigos da PG Soft.')
                ->action(function () {
                    try {
                        // Chama o método fetchGames atualizado que limpa o banco e usa Slugs
                        $response = \App\Http\Controllers\Provider\PGCloneController::fetchGames();
                        $data = $response->getData(true);

                        if ($response->status() === 200) {
                            Notification::make()
                                ->title('Sincronização Concluída!')
                                ->body($data['message'] ?? 'Catálogo e imagens atualizados com sucesso.')
                                ->success()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Erro na API PGClone')
                                ->body($data['error'] ?? 'Não foi possível obter a lista de jogos.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erro Crítico')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('sync_maxapi')
                ->label('Sincronizar MAX API')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $response = \App\Http\Controllers\Provider\MaxApiController::fetchGames();
                        $data = $response->getData(true);

                        if ($response->status() === 200) {
                            Notification::make()
                                ->title('Concluído!')
                                ->body($data['message'] ?? 'Jogos MAX API sincronizados.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Erro MAX API')
                                ->body($data['error'] ?? 'Falha na conexão.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erro Fatal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Configuração de Provedores')
                    ->tabs([
                        
                        // ABA PGCLONE (Priorizada conforme solicitado)
                        Tabs\Tab::make('PGClone (PG Soft)')
                            ->icon('heroicon-o-bolt')
                            ->schema([
                                Section::make('CHAVES DE ACESSO PGCLONE')
                                    ->description('Atenção: Use estes campos para os jogos da PG Soft.')
                                    ->schema([
                                        Toggle::make('pgclone_is_enable')
                                            ->label('Ativar PG Soft no Sistema')
                                            ->inline(false)
                                            ->columnSpanFull(),

                                        TextInput::make('play_gaming_base_url')
                                            ->label('API URL Base')
                                            ->default('https://api.pgclone.com')
                                            ->required(),

                                        TextInput::make('play_gaming_agent_code')
                                            ->label('Código do Agente (Agent Code)'),

                                        TextInput::make('play_gaming_agent_token')
                                            ->label('Token de Acesso (Agent Token)')
                                            ->password()
                                            ->revealable(),

                                        TextInput::make('play_gaming_agent_secret')
                                            ->label('Chave Secreta (Agent Secret)')
                                            ->password()
                                            ->revealable(),
                                    ])->columns(2),
                            ]),

                        // ABA PLAYFIVER
                        Tabs\Tab::make('PlayFiver')
                            ->icon('heroicon-o-play')
                            ->schema([
                                Section::make('PLAYFIVER CONFIG')
                                    ->schema([
                                        Toggle::make('playfiver_is_enable')
                                            ->label('Ativar PlayFiver')
                                            ->columnSpanFull(),

                                        TextInput::make('playfiver_url')
                                            ->label('API URL')
                                            ->default('https://api.playfiver.com')
                                            ->required(),

                                        TextInput::make('playfiver_code')
                                            ->label('Agent Code'),
                                        
                                        TextInput::make('playfiver_token')
                                            ->label('Agent Token'),
                                        
                                        TextInput::make('playfiver_secret')
                                            ->label('Agent Secret')
                                            ->password()
                                            ->revealable(),
                                    ])->columns(2),
                            ]),

                        // ABA MAX API GAMES
                        Tabs\Tab::make('MAX API GAMES')
                            ->icon('heroicon-o-fire')
                            ->schema([
                                Section::make('MAX API v2')
                                    ->schema([
                                        Toggle::make('maxapi_is_enable')
                                            ->label('Ativar MAX API')
                                            ->columnSpanFull(),

                                        TextInput::make('evergame_base_url')
                                            ->label('API URL Base')
                                            ->default('https://maxapigames.com/api/v2')
                                            ->required(),

                                        TextInput::make('evergame_agent_code')
                                            ->label('Agent Code'),

                                        TextInput::make('evergame_agent_token')
                                            ->label('Agent Token')
                                            ->password()
                                            ->revealable(),

                                        TextInput::make('evergame_agent_secret')
                                            ->label('Agent Secret')
                                            ->password()
                                            ->revealable(),
                                    ])->columns(2),
                            ]),

                    ])->columnSpanFull(),

                // SEÇÃO DE SEGURANÇA
                Section::make('Segurança')
                    ->description('Confirme sua senha para aplicar as novas chaves.')
                    ->schema([
                        TextInput::make('approval_password_save')
                            ->label('Senha de Aprovação')
                            ->password()
                            ->required()
                            ->revealable()
                            ->columnSpan(1),
                    ])->columns(2),

            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        try {
            if (config('app.demo')) {
                Notification::make()->title('Atenção')->body('Ação bloqueada no modo demo.')->danger()->send(); 
                return;
            }

            $approvalSettings = AproveSaveSetting::first();
            $inputPassword = $this->data['approval_password_save'] ?? '';

            if (!$approvalSettings || !Hash::check($inputPassword, $approvalSettings->approval_password_save)) {
                Notification::make()->title('Senha Incorreta')->body('Verifique sua senha de aprovação.')->danger()->send(); 
                return;
            }

            $setting = GamesKey::first();
            $updateData = $this->data;
            unset($updateData['approval_password_save']);

            if (!empty($setting)) {
                $setting->update($updateData);
                Notification::make()->title('Configurações salvas!')->success()->send();
            } else {
                GamesKey::create($updateData);
                Notification::make()->title('Configurações criadas!')->success()->send();
            }
        } catch (Halt $exception) {
            Notification::make()->title('Erro de Validação')->danger()->send();
        }
    }
}