<x-volt-base title="Halaman Baru">
    <div class="ui container p-y-5">
        <h1>Halaman Baru</h1>

        {!! form()->open()->route('page.store') !!}

        <div x-data="{ judul: ''}">
            <div class="ui fluid input m-b-1">
                <input type="text" name="judul" placeholder="Judul" x-model="judul" required>
            </div>
            <div class="ui fluid labeled input m-b-1">
                <div class="ui black label">
                    {{ config('app.url') }}/p/
                </div>
                <input type="text" name="slug" placeholder="URL(mis: nama-halaman)" :value="judul.replaceAll(/[^a-zA-Z0-9 ]/g, ' ').replaceAll(/\s+/g, ' ').replaceAll(' ', '-').toLowerCase()" required>
            </div>
        </div>
        {{-- {!! form()->text('judul')->placeholder('Judul')->required()->class('m-b-1') !!}
        {!! form()->text('slug')->placeholder('URL(mis: nama-halaman)')->required()->class('m-b-1') !!} --}}
        <div class="m-b-1">
            {!! form()->redactor('konten')->placeholder('Konten')->required() !!}
        </div>

        {!! form()->textarea('style')->placeholder('Deskripsi Tampilan')->required()->class('m-b-1') !!}

        {!! form()->uploader('files')->limit(50)->extensions(['jpg', 'png', 'gif'])->label('Gambar') !!}

        <div class="ui buttons fluid">
            <x-volt-link-button class="basic" url="{!! url()->previous() !!}" icon="arrow left">
                Kembali
            </x-volt-link-button>
            <x-volt-button>
                Buat
            </x-volt-button>
        </div>
        {!! form()->close() !!}
    </div>
</x-volt-base>
