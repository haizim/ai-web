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

        <div style="width: 100%;text-align: right;margin-top: -4em;padding-right: .5em; margin-bottom: 1em;">
            <button id="generate-style" type="button" class="ui black icon button" onclick="generateStyle()">
                <i class="robot icon"></i>
            </button>
        </div>

        {!! form()->uploader('files')->id('files')->limit(50)->extensions(['jpg', 'png', 'gif'])->label('Gambar') !!}

        <div class="ui buttons fluid">
            <x-volt-link-button class="basic" url="{!! url()->previous() !!}" icon="arrow left">
                Kembali
            </x-volt-link-button>
            <x-volt-button id="submit" type="button" onclick="generate()">
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
    function generate() {
        const judul = document.getElementById('judul').value;
        const slug = document.getElementById('slug').value;
        const konten = document.getElementById('konten').value;
        const style = document.getElementById('style').value;
        const btn = document.getElementById('submit');

        if (judul == '' || slug == '' || konten == '' || style == '') {
            $('#loading-modal').modal('hide');
            btn.disabled = false;
            return false;
        } else {
            $('#loading-modal').modal('show');
            document.getElementById
            btn.disabled = true;
            btn.classList.add('loading')

            const data = getFormData($('#form'));

            fetch('{{ route('api.generate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                window.location.href = '{{ route('page.edit', ':id') }}'.replace(':id', data.id);
            })
            .catch(error => {
                console.error('Error:', error);
            })
            .finally(() => {
                $('#loading-modal').modal('hide');
                btn.disabled = false;
                btn.classList.remove('loading')
            });
            // $('#form').submit();
        }

    }

    function getFormData($form){
        var unindexed_array = $form.serializeArray();
        var indexed_array = {};

        $.map(unindexed_array, function(n, i){
            indexed_array[n['name']] = n['value'];
        });

        return indexed_array;
    }

    function generateStyle() {
        const konten = document.getElementById('konten').value;
        const btn = document.getElementById('generate-style');
        if (!konten) return alert('konten tidak boleh kosong!');

        btn.disabled = true;
        btn.classList.add('loading')
        fetch('{{ route('api.generate-style') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                'konten': konten
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('style').value = data.style;
        })
        .catch(error => {
            console.error('Error:', error);
        })
        .finally(() => {
            document.getElementById('generate-style').disabled = false;
            btn.classList.remove('loading')
        })
    }

</script>
</x-volt-base>
