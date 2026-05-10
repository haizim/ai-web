{!! form()->text('name')->label('Name')->required() !!}
{!! form()->textarea('description')->label('Description')->required() !!}

<x-volt-link-button :url="route('styles.index')" icon="arrow left" label="Cancel" class="secondary" />
<x-volt-button icon="save" label="Save" />
