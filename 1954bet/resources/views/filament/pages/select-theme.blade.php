<x-filament-panels::page>
    <form wire:submit.prevent="submit">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ([
                // Temas Originais
                'pourple'    => ['name' => 'Roxo',           'image' => 'pourple.png'],
                'black'      => ['name' => 'Preto',          'image' => 'black.png'],
                'blue'       => ['name' => 'Azul',           'image' => 'blue.png'],
                'red'        => ['name' => 'Vermelho',       'image' => 'red.png'],
                'pink'       => ['name' => 'Rosa',           'image' => 'pink.png'],
                'limon'      => ['name' => 'Limão',          'image' => 'limon.png'],
                'brown'      => ['name' => 'Marrom',         'image' => 'brown.png'],
                'skyblue'    => ['name' => 'Azul Celeste',   'image' => 'skyblue.png'],
                'whiteblue'  => ['name' => 'Azul e Branco',  'image' => 'whiteblue.png'],
                'greendark'  => ['name' => 'Verde Escuro',   'image' => 'greendark.png'],
                'orange'     => ['name' => 'Laranja',        'image' => 'orange.png'],
                'green'      => ['name' => 'Verde',          'image' => 'green.png'],
                'yellow'     => ['name' => 'Amarelo',        'image' => 'yellow.png'],
                'greenwhite' => ['name' => 'Verde e Branco', 'image' => 'greenwhite.png'],
                'gray'       => ['name' => 'Cinza',          'image' => 'gray.png'],

                // Temas já existentes (Novos Temas anteriores)
                'violet'     => ['name' => 'Violeta',        'image' => 'violet.png'],
                'aqua'       => ['name' => 'Azul Água',      'image' => 'aqua.png'],
                'magenta'    => ['name' => 'Magenta',        'image' => 'magenta.png'],
                'teal'       => ['name' => 'Verde-Azulado',  'image' => 'teal.png'],
                'indigo'     => ['name' => 'Índigo',         'image' => 'indigo.png'],
                'coral'      => ['name' => 'Coral',          'image' => 'coral.png'],
                'navy'       => ['name' => 'Azul Marinho',   'image' => 'navy.png'],
                'mint'       => ['name' => 'Menta',          'image' => 'mint.png'],
                'crimson'    => ['name' => 'Carmesim',       'image' => 'crimson.png'],
                'sapphire'   => ['name' => 'Safira',         'image' => 'sapphire.png'],
                'olive'      => ['name' => 'Verde Oliva',    'image' => 'olive.png'],
                'copper'     => ['name' => 'Cobre',          'image' => 'copper.png'],
                'ruby'       => ['name' => 'Rubi',           'image' => 'ruby.png'],
                'amethyst'   => ['name' => 'Ametista',       'image' => 'amethyst.png'],
                'peach'      => ['name' => 'Pêssego',        'image' => 'peach.png'],

                // 15 Novos Temas Criados (mistura de cores com alto contraste para os textos)
                'fierycontrast'    => ['name' => 'Contraste Flamejante',  'image' => 'fierycontrast.png'],
                'oceansunrise'     => ['name' => 'Amanhecer Oceânico',     'image' => 'oceansunrise.png'],
                'forestglow'       => ['name' => 'Brilho da Floresta',     'image' => 'forestglow.png'],
                'electricsky'      => ['name' => 'Céu Elétrico',           'image' => 'electricsky.png'],
                'urbanchic'        => ['name' => 'Chique Urbano',          'image' => 'urbanchic.png'],
                'sunsetdream'      => ['name' => 'Sonho do Entardecer',     'image' => 'sunsetdream.png'],
                'tropicalvibe'     => ['name' => 'Vibração Tropical',      'image' => 'tropicalvibe.png'],
                'midnightaurora'   => ['name' => 'Aurora da Meia-Noite',     'image' => 'midnightaurora.png'],
                'vibrantcitrus'    => ['name' => 'Cítrico Vibrante',       'image' => 'vibrantcitrus.png'],
                'royalelegance'    => ['name' => 'Elegância Real',         'image' => 'royalelegance.png'],
                'rusticcharm'      => ['name' => 'Charme Rústico',         'image' => 'rusticcharm.png'],
                'icecrystal'       => ['name' => 'Cristal de Gelo',        'image' => 'icecrystal.png'],
                'modernmonochrome' => ['name' => 'Monocromático Moderno',  'image' => 'modernmonochrome.png'],
                'sunnybreeze'      => ['name' => 'Brisa Ensolarada',       'image' => 'sunnybreeze.png'],
                'radiantbloom'     => ['name' => 'Florescimento Radiante', 'image' => 'radiantbloom.png'],
            ] as $key => $theme)
                <div class="border p-4 rounded text-center">
                    <h3 class="font-semibold text-lg mb-2">{{ $theme['name'] }}</h3>
                    <img src="{{ asset('storage/themes/' . $theme['image']) }}" alt="{{ $theme['name'] }}" class="mx-auto my-2 h-20 w-20 object-cover rounded-md">
                    <div class="mt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input
                                type="radio"
                                name="selectedTheme"
                                value="{{ $key }}"
                                wire:model="selectedTheme"
                                class="form-radio"
                            />
                            <span class="ml-2">Selecionar</span>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            <x-filament::button type="submit">Aplicar Tema</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
