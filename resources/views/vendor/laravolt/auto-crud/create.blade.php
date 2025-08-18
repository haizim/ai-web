<x-volt-app :title="$config['label']">
    <x-slot name="actions">
        <x-volt-backlink url="{{ route('auto-crud::resource.index', $config['key']) }}">Kembali ke Index
        </x-volt-backlink>
    </x-slot>

    <x-volt-panel title="Tambah {{ $config['label'] }}">
        {!! form()->post(route('auto-crud::resource.store', $config['key'])) !!}

        {!! form()->make($fields)->bindValues(request()->old() + request()->all())->render() !!}

        {{-- {!! form()->action([
            form()->submit(__('Save')),
            form()->link(__('Cancel'), route('auto-crud::resource.index', $config['key']))
        ]) !!} --}}
        <x-volt-button type="submit">{{ __('Save') }}</x-volt-button>
        <x-volt-link-button class="basic" url="{!! route('auto-crud::resource.index', $config['key']) !!}">
            {{ __('Cancel') }}
        </x-volt-link-button>

        {!! form()->close() !!}
    </x-volt-panel>


</x-volt-app>
