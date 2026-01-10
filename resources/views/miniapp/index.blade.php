<x-volt-app title="Mini App">
    <x-slot name="actions">
        <x-volt-link-button
            :url="route('miniapp.create')"
            icon="plus"
            label="Baru"
        />
    </x-slot>

    @livewire(\App\Livewire\Table\MiniAppTable::class)
</x-volt-app>