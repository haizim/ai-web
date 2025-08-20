<x-volt-app title="Page">
    <x-slot name="actions">
        <x-volt-link-button
            :url="route('page.create')"
            icon="plus"
            label="Baru"
        />
    </x-slot>

    @livewire(\App\Livewire\Table\PageTable::class)
</x-volt-app>
