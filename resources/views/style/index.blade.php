<x-volt-app title="Styles">
    <x-slot name="actions">
        @if (auth()->user()->can(\App\Enums\Permission::STYLES_MANAGE))
            <x-volt-link-button :url="route('styles.create')" icon="plus" label="New" />
        @endif
    </x-slot>

    @livewire(\App\Livewire\Table\StyleTable::class)
</x-volt-app>
