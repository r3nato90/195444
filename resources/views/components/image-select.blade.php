<div class="grid grid-cols-2 gap-4">
    @foreach($getOptions() as $key => $option)
        <label class="cursor-pointer">
            <input
                type="radio"
                name="{{ $getStatePath() }}"
                value="{{ $key }}"
                wire:model.defer="state.{{ $getStatePath() }}"
                class="hidden"
            />
            <div
                class="border-2 rounded-lg overflow-hidden p-2 @if ($getState() === $key) border-blue-500 @else border-gray-300 @endif"
            >
                <img
                    src="{{ asset($option['image']) }}"
                    alt="{{ $option['name'] }}"
                    class="w-full h-auto"
                />
                <div class="text-center mt-2">{{ $option['name'] }}</div>
            </div>
        </label>
    @endforeach
</div>
