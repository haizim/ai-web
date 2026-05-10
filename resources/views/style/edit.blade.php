<x-volt-app title="Edit Style">
    <x-volt-panel title="Form">
        {!! form()->bind($style)->put()->action(route('styles.update', $style->id)) !!}
            @include('style._form')
        {!! form()->close() !!}
    </x-volt-panel>
</x-volt-app>
