<x-volt-app title="Style Detail">
    <x-volt-panel title="Detail">
        <table class="ui very basic table">
            <tbody>
                <tr>
                    <td><strong>Name</strong></td>
                    <td>{{ $style->name }}</td>
                </tr>
                <tr>
                    <td><strong>Description</strong></td>
                    <td>{{ $style->description }}</td>
                </tr>
                <tr>
                    <td><strong>Created At</strong></td>
                    <td>{{ $style->created_at->format('d M Y H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Updated At</strong></td>
                    <td>{{ $style->updated_at->format('d M Y H:i') }}</td>
                </tr>
            </tbody>
        </table>

        <x-volt-link-button :url="route('styles.index')" icon="arrow left" label="Back" />
        @if (auth()->user()->can(\App\Enums\Permission::STYLES_MANAGE))
            <x-volt-link-button :url="route('styles.edit', $style->id)" icon="edit" label="Edit" />
        @endif
    </x-volt-panel>
</x-volt-app>
