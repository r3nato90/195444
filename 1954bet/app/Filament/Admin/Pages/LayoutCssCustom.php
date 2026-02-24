<?php

namespace App\Filament\Admin\Pages;

use App\Models\CustomLayout;
use Creagia\FilamentCodeField\CodeField;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextArea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Slider;
use Illuminate\Validation\Rules\Between;
use Filament\Forms\Components\RichEditor;
use App\Models\AproveSaveSetting;
use Illuminate\Support\Facades\Hash;

class LayoutCssCustom extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.layout-css-custom';

    protected static ?string $navigationLabel = 'Customização Layout';

    protected static ?string $modelLabel = 'Customização Layout';

    protected static ?string $title = 'Customização Layout';

    protected static ?string $slug = 'custom-style-pro';

    public ?array $data = [];
    public CustomLayout $custom;

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
        $this->custom = CustomLayout::first();
        $this->form->fill($this->custom->toArray());
    }

    /**
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {

        return $data;
    }


    protected $skins = [
        'black_yellow' => [
            'name' => 'Grafite com Amarelo',
            'colors' => [
                'primary_color' => '#f7f7f7',
                'title_color' => '#ffffff',
                'text_color' => '#ffffff',
                'sub_text_color' => '#ffffff',
                'placeholder_color' => '#ffffff',
                'background_color_cassino' => '#3d4145',
                'background_base' => '#93979d',
                'background_base_dark' => '#93979d',
                'carousel_banners' => '#000000',
                'carousel_banners_dark' => '#000000',
                'sidebar_color' => '#46494e',
                'sidebar_color_dark' => '#46494e',
                'navtop_color' => '#33363b',
                'navtop_color_dark' => '#33363b',
                'side_menu' => '#61646b',
                'side_menu_dark' => '#61646b',
                'footer_color' => '#33363b',
                'footer_color_dark' => '#33363b',
                'search_border_color' => '#33363b',
                'Border_bottons_and_selected' => '#f4d142',
                'background_bottom_navigation' => '#33363b',
                'background_bottom_navigation_dark' => '#33363b',
                'borders_and_dividers_colors' => '#4e5359',
                'search_back' => '#4e5359',
                'color_bt_1' => '#3E95FE',
                'color_bt_2' => '#F45C4E',
                'color_bt_3' => '#ca50f3',
                'color_bt_4' => '#f4d142',
                'value_color_jackpot' => '#fefe93',
                'value_wallet_navtop' => '#f4d142',
                'bonus_color_dep' => '#f4d142',
                'colors_deposit_value' => '#f4d142',
                'color_players' => '#ffffff',
                'placeholder_background' => '#33363b',
                'card_transaction' => '#46494e',
                'back_sub_color' => '#3d4145',
                'item_sub_color' => '#000000',
                'text_sub_color' => '#ffffff',
                'title_sub_color' => '#ffffff',
                'botao_deposito_background_nav' => '#f4d142',
                'botao_deposito_text_nav' => '#000000',
                'botao_login_background_nav' => '#f4d142',
                'botao_login_text_nav' => '#000000',
                'botao_registro_text_nav' => '#ffffff',
                'botao_login_background_modal' => '#f4d142',
                'botao_login_text_modal' => '#000000',
                'botao_registro_background_modal' => '#f4d142',
                'botao_registro_text_modal' => '#000000',
                'botao_registro_border_nav' => '#ffffff',
                'botao_login_border_nav' => '',
                'botao_deposito_border_nav' => '',
                'botao_deposito_text_dep' => '#000000',
                'botao_deposito_background_dep' => '#f4d142',
                'botao_deposito_border_dep' => '#f4d142',
                'botao_deposito_text_saq' => '#ffffff',
                'botao_deposito_border_saq' => '#010463',
                'background_color_category' => '#33363b',
                'background_color_jackpot' => '#33363b',
                'navb_icon_color' => '#f4d142',
            ]
        ],
        'black_green' => [
            'name' => 'Grafite com Verde',
            'colors' => [
                'primary_color' => '#f7f7f7',
                'title_color' => '#ffffff',
                'text_color' => '#ffffff',
                'sub_text_color' => '#ffffff',
                'placeholder_color' => '#ffffff',
                'background_color_cassino' => '#3d4145',
                'background_base' => '#93979d',
                'background_base_dark' => '#93979d',
                'carousel_banners' => '#000000',
                'carousel_banners_dark' => '#000000',
                'sidebar_color' => '#46494e',
                'sidebar_color_dark' => '#46494e',
                'navtop_color' => '#33363b',
                'navtop_color_dark' => '#33363b',
                'side_menu' => '#61646b',
                'side_menu_dark' => '#61646b',
                'footer_color' => '#33363b',
                'footer_color_dark' => '#33363b',
                'search_border_color' => '#33363b',
                'Border_bottons_and_selected' => '#38e040',
                'background_bottom_navigation' => '#33363b',
                'background_bottom_navigation_dark' => '#33363b',
                'borders_and_dividers_colors' => '#4e5359',
                'search_back' => '#4e5359',
                'color_bt_1' => '#3E95FE',
                'color_bt_2' => '#F45C4E',
                'color_bt_3' => '#ca50f3',
                'color_bt_4' => '#38e040',
                'value_color_jackpot' => '#fefe93',
                'value_wallet_navtop' => '#38e040',
                'bonus_color_dep' => '#38e040',
                'colors_deposit_value' => '#38e040',
                'color_players' => '#ffffff',
                'placeholder_background' => '#33363b',
                'card_transaction' => '#46494e',
                'back_sub_color' => '#3d4145',
                'item_sub_color' => '#000000',
                'text_sub_color' => '#ffffff',
                'title_sub_color' => '#ffffff',
                'botao_deposito_background_nav' => '#38e040',
                'botao_deposito_text_nav' => '#000000',
                'botao_login_background_nav' => '#38e040',
                'botao_login_text_nav' => '#000000',
                'botao_registro_text_nav' => '#ffffff',
                'botao_login_background_modal' => '#38e040',
                'botao_login_text_modal' => '#000000',
                'botao_registro_background_modal' => '#38e040',
                'botao_registro_text_modal' => '#000000',
                'botao_registro_border_nav' => '#ffffff',
                'botao_login_border_nav' => '',
                'botao_deposito_border_nav' => '',
                'botao_deposito_text_dep' => '#000000',
                'botao_deposito_background_dep' => '#38e040',
                'botao_deposito_border_dep' => '#38e040',
                'botao_deposito_text_saq' => '#ffffff',
                'botao_deposito_border_saq' => '#010463',
                'background_color_category' => '#33363b',
                'background_color_jackpot' => '#33363b',
                'navb_icon_color' => '#38e040',
            ]
        ],
        'blue' => [
            'name' => 'Azul',
            'colors' => [
                'primary_color' => '#f7f7f7',
                'title_color' => '#ffffff',
                'text_color' => '#ffffff',
                'sub_text_color' => '#ffffff',
                'placeholder_color' => '#ffffff',
                'background_color_cassino' => '#3187f0',
                'background_base' => '#5e97e2',
                'background_base_dark' => '#5e97e2',
                'carousel_banners' => '#3187f0',
                'carousel_banners_dark' => '#3187f0',
                'sidebar_color' => '#1a6ad5',
                'sidebar_color_dark' => '#1a6ad5',
                'navtop_color' => '#025dd4',
                'navtop_color_dark' => '#025dd4',
                'side_menu' => '#2c7be4',
                'side_menu_dark' => '#2c7be4',
                'footer_color' => '#025dd4',
                'footer_color_dark' => '#025dd4',
                'search_border_color' => '#151d29',
                'Border_bottons_and_selected' => '#f4d142',
                'background_bottom_navigation' => '#025dd4',
                'background_bottom_navigation_dark' => '#025dd4',
                'borders_and_dividers_colors' => '#3187f0',
                'search_back' => '#025dd4',
                'color_bt_1' => '#3E95FE',
                'color_bt_2' => '#F45C4E',
                'color_bt_3' => '#ca50f3',
                'color_bt_4' => '#fbaf01',
                'value_color_jackpot' => '#fefe93',
                'value_wallet_navtop' => '#f4d142',
                'bonus_color_dep' => '#f4d142',
                'colors_deposit_value' => '#f4d142',
                'color_players' => '#ffffff',
                'placeholder_background' => '#025dd4',
                'card_transaction' => '#3187f0',
                'back_sub_color' => '#3187f0',
                'item_sub_color' => '#025dd4',
                'text_sub_color' => '#ffffff',
                'title_sub_color' => '#ffffff',
                'botao_deposito_background_nav' => '#eab700',
                'botao_deposito_text_nav' => '#000000',
                'botao_login_background_nav' => '#f4d142',
                'botao_login_text_nav' => '#000000',
                'botao_registro_text_nav' => '#ffffff',
                'botao_login_background_modal' => '#f4d142',
                'botao_login_text_modal' => '#000000',
                'botao_registro_background_modal' => '#f4d142',
                'botao_registro_text_modal' => '#000000',
                'botao_registro_border_nav' => '#ffffff',
                'botao_login_border_nav' => '#',
                'botao_deposito_border_nav' => '#',
                'botao_deposito_text_dep' => '#000000',
                'botao_deposito_background_dep' => '#f4d142',
                'botao_deposito_border_dep' => '#f4d142',
                'botao_deposito_text_saq' => '#ffffff',
                'botao_deposito_border_saq' => '#010463',
                'background_color_category' => '#025dd4',
                'background_color_jackpot' => '#025dd4',
                'navb_icon_color' => '#ffffff',
            ]
        ],
        'purple' => [
            'name' => 'Roxo',
            'colors' => [
                'primary_color' => '#f7f7f7',
                'title_color' => '#ffffff',
                'text_color' => '#ffffff',
                'sub_text_color' => '#ffffff',
                'placeholder_color' => '#ffffff',
                'background_color_cassino' => '#9431f0',
                'background_base' => '#af5ee2',
                'background_base_dark' => '#af5ee2',
                'carousel_banners' => '#a731f0',
                'carousel_banners_dark' => '#a731f0',
                'sidebar_color' => '#7b1ad5',
                'sidebar_color_dark' => '#7b1ad5',
                'navtop_color' => '#8002d4',
                'navtop_color_dark' => '#8002d4',
                'side_menu' => '#ab3fff',
                'side_menu_dark' => '#ab3fff',
                'footer_color' => '#8002d4',
                'footer_color_dark' => '#8002d4',
                'search_border_color' => '#151d29',
                'Border_bottons_and_selected' => '#f4d142',
                'background_bottom_navigation' => '#8002d4',
                'background_bottom_navigation_dark' => '#8002d4',
                'borders_and_dividers_colors' => '#a731f0',
                'search_back' => '#8002d4',
                'color_bt_1' => '#3E95FE',
                'color_bt_2' => '#F45C4E',
                'color_bt_3' => '#ca50f3',
                'color_bt_4' => '#fbaf01',
                'value_color_jackpot' => '#fefe93',
                'value_wallet_navtop' => '#f4d142',
                'bonus_color_dep' => '#f4d142',
                'colors_deposit_value' => '#f4d142',
                'color_players' => '#ffffff',
                'placeholder_background' => '#8002d4',
                'card_transaction' => '#a731f0',
                'back_sub_color' => '#a731f0',
                'item_sub_color' => '#8002d4',
                'text_sub_color' => '#ffffff',
                'title_sub_color' => '#ffffff',
                'botao_deposito_background_nav' => '#eab700',
                'botao_deposito_text_nav' => '#000000',
                'botao_login_background_nav' => '#f4d142',
                'botao_login_text_nav' => '#000000',
                'botao_registro_text_nav' => '#ffffff',
                'botao_login_background_modal' => '#f4d142',
                'botao_login_text_modal' => '#000000',
                'botao_registro_background_modal' => '#f4d142',
                'botao_registro_text_modal' => '#000000',
                'botao_registro_border_nav' => '#ffffff',
                'botao_login_border_nav' => '#',
                'botao_deposito_border_nav' => '#',
                'botao_deposito_text_dep' => '#000000',
                'botao_deposito_background_dep' => '#f4d142',
                'botao_deposito_border_dep' => '#f4d142',
                'botao_deposito_text_saq' => '#ffffff',
                'botao_deposito_border_saq' => '#010463',
                'background_color_category' => '#8002d4',
                'background_color_jackpot' => '#8002d4',
                'navb_icon_color' => '#ffffff',
            ]
        ],
        'green' => [
            'name' => 'Verde',
            'colors' => [
                'primary_color' => '#f7f7f7',
                'title_color' => '#ffffff',
                'text_color' => '#ffffff',
                'sub_text_color' => '#ffffff',
                'placeholder_color' => '#ffffff',
                'background_color_cassino' => '#1d91a4',
                'background_base' => '#1caeb2',
                'background_base_dark' => '#1caeb2',
                'carousel_banners' => '#23b6c5',
                'carousel_banners_dark' => '#23b6c5',
                'sidebar_color' => '#10657b',
                'sidebar_color_dark' => '#10657b',
                'navtop_color' => '#007588',
                'navtop_color_dark' => '#007588',
                'side_menu' => '#2a879b',
                'side_menu_dark' => '#2a879b',
                'footer_color' => '#007588',
                'footer_color_dark' => '#007588',
                'search_border_color' => '#151d29',
                'Border_bottons_and_selected' => '#f4d142',
                'background_bottom_navigation' => '#007588',
                'background_bottom_navigation_dark' => '#007588',
                'borders_and_dividers_colors' => '#23b6c5',
                'search_back' => '#007588',
                'color_bt_1' => '#3E95FE',
                'color_bt_2' => '#F45C4E',
                'color_bt_3' => '#ca50f3',
                'color_bt_4' => '#fbaf01',
                'value_color_jackpot' => '#fefe93',
                'value_wallet_navtop' => '#f4d142',
                'bonus_color_dep' => '#f4d142',
                'colors_deposit_value' => '#f4d142',
                'color_players' => '#ffffff',
                'placeholder_background' => '#007588',
                'card_transaction' => '#23b6c5',
                'back_sub_color' => '#23b6c5',
                'item_sub_color' => '#007588',
                'text_sub_color' => '#ffffff',
                'title_sub_color' => '#ffffff',
                'botao_deposito_background_nav' => '#eab700',
                'botao_deposito_text_nav' => '#000000',
                'botao_login_background_nav' => '#f4d142',
                'botao_login_text_nav' => '#000000',
                'botao_registro_text_nav' => '#ffffff',
                'botao_login_background_modal' => '#f4d142',
                'botao_login_text_modal' => '#000000',
                'botao_registro_background_modal' => '#f4d142',
                'botao_registro_text_modal' => '#000000',
                'botao_registro_border_nav' => '#ffffff',
                'botao_login_border_nav' => '#',
                'botao_deposito_border_nav' => '#',
                'botao_deposito_text_dep' => '#000000',
                'botao_deposito_background_dep' => '#f4d142',
                'botao_deposito_border_dep' => '#f4d142',
                'botao_deposito_text_saq' => '#ffffff',
                'botao_deposito_border_saq' => '#010463',
                'background_color_category' => '#007588',
                'background_color_jackpot' => '#007588',
                'navb_icon_color' => '#ffffff',
            ]
        ],
    ];


    public function applySkinColors($selectedSkin)
    {
        // Verifica se a skin selecionada existe
        if (isset($this->skins[$selectedSkin])) {
            $skin = $this->skins[$selectedSkin];

            // Aplica as cores com base na skin selecionada
            foreach ($skin['colors'] as $colorKey => $colorValue) {
                $this->data[$colorKey] = $colorValue;  // Presumindo que '$this->data' seja uma variável de estado reativo
            }
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

                Section::make('Escolher Cores pré-definidas')
                ->description('A escolha de uma cor será aplicada após a confirmação do save.')
                ->schema([
                    Select::make('selected_skin')
                        ->label('Escolha uma cor pré-definida e aplique:')
                        ->options(
                            collect($this->skins)
                                ->mapWithKeys(fn($skin, $key) => [
                                    $key => $skin['name']  // Apenas o nome da skin
                                ])
                                ->toArray()
                        )
                        ->reactive()
                        ->afterStateUpdated(function ($state) {
                            if (isset($this->skins[$state])) {
                                $this->applySkinColors($state); // Aplica as cores da skin
                            }
                        })
                ]),

                Section::make()
                    ->label('Background')
                    ->schema([
                        ColorPicker::make('background_base')
                            ->label('Background Principal')
                            ->required(),
                        ColorPicker::make('background_base_dark')
                            ->label('Background Principal (Dark)')
                            ->required(),
                        ColorPicker::make('carousel_banners')
                            ->label('Carousel Banners')
                            ->required(),
                        ColorPicker::make('carousel_banners_dark')
                            ->label('Carousel Banners (Dark)')
                            ->required(),
                    ])->columns(4)
                ,
                Section::make('Sidebar & NavTop & Footer & BottomNavigation')
                    ->description('Personalize a aparência do seu site, conferindo-lhe uma identidade única.')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        ColorPicker::make('sidebar_color')
                            ->label('Sidebar')
                            ->required(),

                        ColorPicker::make('sidebar_color_dark')
                            ->label('Sidebar (Dark)')
                            ->required(),

                        ColorPicker::make('navtop_color')
                            ->label('Navtop')
                            ->required(),

                        ColorPicker::make('navtop_color_dark')
                            ->label('Navtop (Dark)')
                            ->required(),

                        ColorPicker::make('side_menu')
                            ->label('Side Menu Box')
                            ->required(),

                        ColorPicker::make('side_menu_dark')
                            ->label('Side Menu Box (Dark)')
                            ->required(),

                        ColorPicker::make('footer_color')
                            ->label('Footer Color')
                            ->required(),

                        ColorPicker::make('footer_color_dark')
                            ->label('Footer Color (Dark)')
                            ->required(),
                        ColorPicker::make('background_bottom_navigation')
                            ->label('Bottom Navigation')
                            ->required(),

                        ColorPicker::make('background_bottom_navigation_dark')
                            ->label('Bottom Navigation (Dark)')
                            ->required(),

                        ColorPicker::make('navb_icon_color')
                        ->label('Cor dos icones do Bottom Navigation')
                        ->required(),

                        ColorPicker::make('value_wallet_navtop')
                            ->label('Cor do valor do saldo na NAVTOP')
                            ->required(),
                    ])->columns(4),
                Section::make('Cores de botões')
                    ->description('Personalize as cores dos botões')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        ColorPicker::make('botao_deposito_background_nav')
                            ->label('Background Botão Depósito NAVTOP')
                            ->required(),

                        ColorPicker::make('botao_deposito_text_nav')
                            ->label('Texto Botão Depósito NAVTOP')
                            ->required(),
                        ColorPicker::make('botao_deposito_border_nav')
                            ->label('Borda Botão Depósito NAVTOP')
                            ->required(),

                        ColorPicker::make('botao_login_background_nav')
                            ->label('Background Botão Login NAVTOP')
                            ->required(),

                        ColorPicker::make('botao_login_text_nav')
                            ->label('Texto Botão Login NAVTOP')
                            ->required(),
                        ColorPicker::make('botao_login_border_nav')
                            ->label('Borda Botão Login NAVTOP')
                            ->required(),

                        ColorPicker::make('botao_registro_background_nav')
                            ->label('Background Botão Registro NAVTOP')
                            ->required(),

                        ColorPicker::make('botao_registro_text_nav')
                            ->label('Texto Botão Registro NAVTOP')
                            ->required(),
                        ColorPicker::make('botao_registro_border_nav')
                            ->label('Borda Botão Registro NAVTOP')
                            ->required(),

                        ColorPicker::make('botao_login_background_modal')
                            ->label('Background Botão Login Modal')
                            ->required(),

                        ColorPicker::make('botao_login_text_modal')
                            ->label('Texto Botão Login Modal')
                            ->required(),

                        ColorPicker::make('botao_registro_background_modal')
                            ->label('Background Botão Registro Modal')
                            ->required(),

                        ColorPicker::make('botao_registro_text_modal')
                            ->label('Texto Botão Registro Modal')
                            ->required(),


                        ColorPicker::make('botao_deposito_background_dep')
                            ->label('Background Botão Depósito na tela de depósito')
                            ->required(),

                        ColorPicker::make('botao_deposito_text_dep')
                            ->label('Texto Botão Depósito na tela de depósito')
                            ->required(),
                        ColorPicker::make('botao_deposito_border_dep')
                            ->label('Borda Botão Depósito na tela de depósito')
                            ->required(),

                        ColorPicker::make('botao_deposito_background_saq')
                            ->label('Background Botão Saque na tela de saque')
                            ->required(),

                        ColorPicker::make('botao_deposito_text_saq')
                            ->label('Texto Botão Saque na tela de saque')
                            ->required(),
                        ColorPicker::make('botao_deposito_border_saq')
                            ->label('Borda Botão Saque na tela de saque')
                            ->required(),
                    ])
                    ->columns(4),


                    Section::make('Customização dos TEXTOS')
                    ->description('Personalize a aparência como desejar')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        ColorPicker::make('title_color')
                            ->label('Title Color')
                            ->required(),
                        ColorPicker::make('text_color')
                            ->label('Text Color')
                            ->required(),
                        ColorPicker::make('sub_text_color')
                            ->label('Sub Text Color')
                            ->required(),
                        ColorPicker::make('placeholder_color')
                            ->label('Placeholder Color')
                            ->required(),
                        TextInput::make('text_opacity')
    ->label('Opacidade do Texto')
    ->numeric() // Define como numérico
    ->step(0.01) // Permite precisão decimal
    ->default(1) // Valor padrão é 1 (100%)
    ->minValue(0) // Valor mínimo (0% de opacidade)
    ->maxValue(1) // Valor máximo (100% de opacidade)
    ->suffix('%')
    ->helperText('Defina a opacidade do texto de 0 a 1, onde 0 é totalmente transparente e 1 é totalmente opaco.')
    ->required(),
                       TextInput::make('text_color_footer')
                            ->label('Opacidade do Texto do Footer')
                            ->numeric() // Define como numérico
    ->step(0.01) // Permite precisão decimal
    ->default(1) // Valor padrão é 1 (100%)
    ->minValue(0) // Valor mínimo (0% de opacidade)
    ->maxValue(1) // Valor máximo (100% de opacidade)
    ->suffix('%')
    ->helperText('Defina a opacidade do texto de 0 a 1, onde 0 é totalmente transparente e 1 é totalmente opaco.')
    ->required(),
                         TextInput::make('opacity_categories')
                            ->label('Opacidade do Texto das categorias')
                            ->numeric() // Define como numérico
    ->step(0.01) // Permite precisão decimal
    ->default(1) // Valor padrão é 1 (100%)
    ->minValue(0) // Valor mínimo (0% de opacidade)
    ->maxValue(1) // Valor máximo (100% de opacidade)
    ->suffix('%')
    ->helperText('Defina a opacidade do texto de 0 a 1, onde 0 é totalmente transparente e 1 é totalmente opaco.')
    ->required(),
      TextInput::make('opacity_bottom_nav')
                            ->label('Opacidade do Texto do Bottom')
                            ->numeric() // Define como numérico
    ->step(0.01) // Permite precisão decimal
    ->default(1) // Valor padrão é 1 (100%)
    ->minValue(0) // Valor mínimo (0% de opacidade)
    ->maxValue(1) // Valor máximo (100% de opacidade)
    ->suffix('%')
    ->helperText('Defina a opacidade do texto de 0 a 1, onde 0 é totalmente transparente e 1 é totalmente opaco.')
    ->required(),


                    ])->columns(4),







                Section::make('Customização')
                    ->description('Personalize a aparência do seu site, conferindo-lhe uma identidade única.')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                       
                        ColorPicker::make('placeholder_background')
                            ->label('Placeholder Background Color')
                            ->required(),
                        ColorPicker::make('card_transaction')
                            ->label('Background transações')
                            ->required(),
                        ColorPicker::make('bonus_color_dep')
                            ->label('Cor do bonus na pagina de deposito')
                            ->required(),
                        ColorPicker::make('background_color_cassino')
                            ->label('Background Color')
                            ->required(),
                         ColorPicker::make('background_color_category')
                            ->label('Background Color categoria')
                            ->required(),
                         ColorPicker::make('background_color_jackpot')
                            ->label('Background Color jackpot')
                            ->required(),
                        ColorPicker::make('Border_bottons_and_selected')
                            ->label('Bordas De Botões e itens selecionados')
                            ->required(),
                        ColorPicker::make('search_border_color')
                            ->label('Search Border Color')
                            ->required(),
                        ColorPicker::make('borders_and_dividers_colors')
                            ->label('Borders And Dividers Colors')
                            ->required(),
                        ColorPicker::make('search_back')
                            ->label('Search Background')
                            ->required(),
                        ColorPicker::make('colors_deposit_value')
                            ->label('Valores na tela de depósito')
                            ->required(),
                        ColorPicker::make('color_players')
                            ->label('Cor dos icones de musica')
                            ->required(),
                        TextInput::make('border_radius')
                            ->label('Border Radius')
                            ->required(),
                    ])->columns(4),


                Section::make('Jackpot')
                    ->description('Jackpot e suporte telegram flutuante')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([ 
                        Toggle::make('disable_jackpot')
                            ->label('Ativar Jackpot')
                            ->helperText('Se tiver desmarcado é porque está inativo o Jackpot de Bônus da Home'),
                            ColorPicker::make('value_color_jackpot')
                            ->label('Cor do Valor Do Jackpot')
                    ])->columns(3),


                    Section::make('Outras funções')
                    ->description('Permite habilitar outras funções do website')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([ 

                        Toggle::make('disable_slider_text')
                            ->label('Ativar Slider de Mensagens na Home')
                            ->helperText('Se tiver marcado é porque está ativo.'),
                        
                        Toggle::make('disable_button_float')
                            ->label('Ativar Botão Flutuante (PROMOÇÕES) na Home')
                            ->helperText('Se tiver marcado é porque está ativo.'),
                        
                        Toggle::make('disable_last_winners')
                            ->label('Ativar últimos Ganhadores na Home')
                            ->helperText('Exibe lista de 60 ganhadores fakes.'),
                        
                        TextInput::make('bt_5_link')
                            ->label('Botão flutuante na Home para suporte')
                            ->placeholder('Digite o LINK do botão 5')
                            ->maxLength(191),

                        Toggle::make('disable_jackpot')
                            ->label('Ativar Jackpot')
                            ->helperText('Se tiver desmarcado é porque está inativo o Jackpot de Bônus da Home'),
                     
                    ])->columns(1),

                Section::make('Customização subordinados')
                    ->description('Personalize a aparência dossubordinados')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        ColorPicker::make('back_sub_color')
                            ->label('Background color subordinados')
                            ->required(),
                        ColorPicker::make('item_sub_color')
                            ->label('Item Color subordinados')
                            ->required(),
                        ColorPicker::make('text_sub_color')
                            ->label('Text Color subordinados')
                            ->required(),
                        ColorPicker::make('title_sub_color')
                            ->label('Title Color subordinados')
                            ->required(),
                    ])->columns(4),
                Section::make('Termos de serviço')
                    ->description('Adicione os termos de serviços aqui')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        RichEditor::make('modal_termos_register')
                            ->label('Termos de registro')
                            ->placeholder('Digite os termos de registro')
                            ->columnSpanFull()
                            ->extraAttributes([
                                'data-tinymce-config' => json_encode([
                                    'plugins' => 'lists code', // Inclua apenas os plugins necessários
                                    'toolbar' => 'bold italic underline strikethrough blockquote code numlist bullist', // Exclua os botões de link e imagem
                                ]),
                            ]),
                    ])->columns(),

                Section::make('Filtro dos icones do PERFIL')
                    ->description('Adicione as configurações aqui')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        TextInput::make('invert_percentage')
                            ->label('Invert (%) (0 a 100)')
                            ->numeric() // Define como campo numérico
                            ->step(0.01) // Define o passo (não aplicável diretamente, mas útil para validação)
                            ->default(100)
                            ->suffix('%'),

                        TextInput::make('sepia_percentage')
                            ->label('Sepia (%) (0 a 100)')
                            ->numeric()
                            ->step(0.01)
                            ->default(5)
                            ->suffix('%'),

                        TextInput::make('saturate_percentage')
                            ->label('Saturate (%) (0 a 1000)')
                            ->numeric()
                            ->step(0.01)
                            ->default(500)
                            ->suffix('%'),

                        TextInput::make('hue_rotate_deg')
                            ->label('Hue Rotate (deg) (-360 a 360)')
                            ->numeric()
                            ->step(0.01)
                            ->default(190),

                        TextInput::make('brightness_percentage')
                            ->label('Brightness (%) (0 a 200)')
                            ->numeric()
                            ->step(0.01)
                            ->default(100)
                            ->suffix('%'),

                        TextInput::make('contrast_percentage')
                            ->label('Contrast (%) (0 a 200)')
                            ->numeric()
                            ->step(0.01)
                            ->default(100)
                            ->suffix('%'),
                    ])
                    ->columns(5),

                Section::make('Links Sociais')
                    ->description('Customize link das suas redes sociais')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        TextInput::make('instagram')
                            ->label('Instagram')
                            ->placeholder('Digite a URL do seu Instagram')
                            ->url()
                            ->maxLength(191),
                        TextInput::make('facebook')
                            ->label('Facebook')
                            ->placeholder('Digite a URL do seu Facebook')
                            ->url()
                            ->maxLength(191),
                        TextInput::make('telegram')
                            ->label('Telegram')
                            ->placeholder('Digite a URL do seu Telegram')
                            ->url()
                            ->maxLength(191),
                        TextInput::make('twitter')
                            ->label('Twitter')
                            ->placeholder('Digite a URL do seu Twitter')
                            ->url()
                            ->maxLength(191),
                        TextInput::make('whastapp')
                            ->label('Whastapp')
                            ->placeholder('Digite a URL do seu Whastapp')
                            ->url()
                            ->maxLength(191),
                        TextInput::make('youtube')
                            ->label('Youtube')
                            ->placeholder('Digite a URL do seu Youtube')
                            ->url()
                            ->maxLength(191),
                    ])->columns(2),
                Section::make('SENHA DE CONFIRMAÇÃO')
                    ->description('DIGITE SUA SENHA PARA QUE SUAS CONFIGURAÇÕES SEJAM SALVAS')
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

            if (!Hash::check($inputPassword, $approvalSettings->approval_password_save)) {
                Notification::make()
                    ->title('Erro de Autenticação')
                    ->body('Senha incorreta. Por favor, tente novamente.')
                    ->danger()
                    ->send();
                return;
            }

            $custom = CustomLayout::first();

            if (!empty($custom)) {
                if ($custom->update($this->data)) {

                    Cache::put('custom', $custom);

                    Notification::make()
                        ->title('Dados alterados')
                        ->body('Dados alterados com sucesso!')
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
