<x-volt-app title="Create Style">
    <x-volt-panel title="Form">
        {!! form()->open()->post()->action(route('styles.store')) !!}
            @include('style._form')
        {!! form()->close() !!}
    </x-volt-panel>
</x-volt-app>
