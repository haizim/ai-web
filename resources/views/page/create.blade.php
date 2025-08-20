<x-volt-base title="Halaman Baru">
    <div class="ui container p-y-5">
        <h1>Halaman Baru</h1>

        {!! form()->open()->route('page.store')->id('form') !!}

        <div x-data="{ judul: ''}">
            <div class="ui fluid input m-b-1">
                <input type="text" name="judul" id="judul" placeholder="Judul" x-model="judul" required>
            </div>
            <div class="ui fluid labeled input m-b-1">
                <div class="ui black label">
                    {{ config('app.url') }}/p/
                </div>
                <input type="text" name="slug" id="slug" placeholder="URL(mis: nama-halaman)" :value="judul.replaceAll(/[^a-zA-Z0-9 ]/g, ' ').replaceAll(/\s+/g, ' ').replaceAll(' ', '-').toLowerCase()" required>
            </div>
        </div>
        {{-- {!! form()->text('judul')->placeholder('Judul')->required()->class('m-b-1') !!}
        {!! form()->text('slug')->placeholder('URL(mis: nama-halaman)')->required()->class('m-b-1') !!} --}}
        <div class="m-b-1">
            {!! form()->redactor('konten')->id('konten')->placeholder('Konten')->required() !!}
        </div>

        {!! form()->textarea('style')->id('style')->placeholder('Deskripsi Tampilan')->required()->class('m-b-1') !!}

        {!! form()->uploader('files')->limit(50)->extensions(['jpg', 'png', 'gif'])->label('Gambar') !!}

        <div class="ui buttons fluid">
            <x-volt-link-button class="basic" url="{!! url()->previous() !!}" icon="arrow left">
                Kembali
            </x-volt-link-button>
            <x-volt-button id="submit">
                Buat
            </x-volt-button>
        </div>
        {!! form()->close() !!}
    </div>

<div class="ui basic modal" id="loading-modal">
    <div class="ui icon header">
        <i class="asterisk loading icon"></i>
    </div>
</div>
<script>
    function validate() {
        const judul = document.getElementById('judul').value;
        const slug = document.getElementById('slug').value;
        const konten = document.getElementById('konten').value;
        const style = document.getElementById('style').value;

        if (judul == '' || slug == '' || konten == '' || style == '') {
            $('#loading-modal').modal('hide');
            document.getElementById('submit').disabled = false;
            return false;
        } else {
            $('#loading-modal').modal('show');
            document.getElementById('submit').disabled = true;

            $('#form').submit();
        }

    }
</script>
</x-volt-base>
