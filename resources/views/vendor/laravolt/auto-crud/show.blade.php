<x-volt-app :title="$config['label']">
    <x-slot name="actions">
        <x-volt-backlink url="{{ route('auto-crud::resource.index', $config['key']) }}">Kembali ke Index
        </x-volt-backlink>
    </x-slot>

    <x-volt-panel title="Detail {{ $config['label'] }} #{{ $model->getKey() }}">
        {{-- {!! form()->make($fields)->bindValues($model->toArray())->display() !!}
        @dump($fields, $model->toArray()) --}}

        <div class="ui grid">
            @foreach ($fields as $field)
            <div class="four wide column"><b>{{ $field['label'] }}</b></div>
            <div class="twelve wide column">{{ $model->toArray()[$field['name']] }}</div>
            @endforeach
        </div>

        <br>
        
        <x-volt-link-button class="basic" url="{!! route('auto-crud::resource.index', $config['key']) !!}">
            Kembali
        </x-volt-link-button>
    </x-volt-panel>


</x-volt-app>
