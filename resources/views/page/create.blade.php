<x-volt-base title="Halaman Baru">
{!! form()->open()->route('page.store')->id('form') !!}
<div x-data="editPage()" style="height: 100vh; overflow: hidden;">
    <div class="ui grid">
        <div :class="code_size + ' wide column p-l-2 p-b-0'" :style="code_size == 'zero' ? 'display: none' : ''">
            <h1 class="m-1">Halaman Baru</h1>
            
            <div class="ui styled fluid accordion m-b-1">
                <div class="active title">
                    <i class="dropdown icon"></i>
                    Judul & URL
                </div>
                <div class="active content">
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
                </div>

                <div class="title">
                    <i class="dropdown icon"></i>
                    Konten
                </div>
                <div class="content" style="max-height: 70vh; overflow-y: scroll">
                    {!! form()->redactor('konten')->id('konten')->placeholder('Konten')->required() !!}
                </div>

                <div class="title">
                    <i class="dropdown icon"></i>
                    Deskripsi Tampilan
                </div>
                <div class="content">
                    {!! form()->textarea('style')->id('style')->placeholder('Deskripsi Tampilan')->required()->class('m-b-1') !!}

                    <div style="width: 100%;text-align: right;margin-top: -4em;padding-right: .5em; margin-bottom: 1em;">
                        <button id="generate-style" type="button" class="ui black icon button" onclick="generateStyle()">
                            <i class="robot icon"></i>
                        </button>
                    </div>
                </div>

                <div class="title">
                    <i class="dropdown icon"></i>
                    Gambar
                </div>
                <div class="content" style="max-height: 70vh; overflow-y: scroll">
                    {!! form()->uploader('files')->id('files')->limit(50)->extensions(['jpg', 'png', 'gif'])->label('Gambar') !!}
                </div>

            </div>

            <textarea id="html" name="html" onchange="updatePreview()" style="display: none"></textarea>
            <x-volt-button type="button" id="preview" class="fluid m-b-1" onclick="generatePreview()">Generate Tampilan</x-volt-button>

            <div class="ui icon fluid buttons">
                <x-volt-link-button class="basic" url="{!! url()->previous() !!}" icon="arrow left">
                    Kembali
                </x-volt-link-button>
                <x-volt-button id="submit">
                    Simpan
                </x-volt-button>
            </div>
        </div>
        
        <div :class="preview_size + ' wide column p-l-0 p-b-0'">
            <iframe id="preview-frame" style="width: 100%; height: 100vh; border: 2px solid #888" sandbox="allow-scripts">
            </iframe>
        </div>
    </div>
    <div style="position: fixed; bottom: 1em; left: 1em;">
        <div class="ui icon buttons">
            <button type="button" class="ui black button" @click="setSize('zero', 'sixteen')"><i class="expand icon"></i></button>
            <button type="button" class="ui black button" @click="setSize('eight', 'eight')"><i class="columns icon"></i></button>
            <button type="button" class="ui black button" @click="setSize('twelve', 'four')"><i class="mobile icon"></i></button>
        </div>
    </div>
</div>
{!! form()->close() !!}

<script>
$('.ui.accordion').accordion()
function editPage() {
    return {
        'title': 'Edit Halaman',
        'code_size': 'eight',
        'preview_size': 'eight',
        
        setSize(code, preview) {
            this.code_size = code;
            this.preview_size = preview;
        }
    }
}

function generateStyle() {
    const konten = document.getElementById('konten').value;
    const btn = document.getElementById('generate-style');
    const style = $('#style').val();
    if (!konten) return alert('konten tidak boleh kosong!');

    btn.disabled = true;
    btn.classList.add('loading')
    fetch('{{ route('api.generate-style') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'konten': konten,
            'style': style
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('style').value = data.style;
    })
    .catch(error => {
        console.error('Error:', error);
        $.toast({
            class: 'error',
            position: 'top center',
            message: `Terjadi Error, silahkan coba lagi !`
        })
    })
    .finally(() => {
        document.getElementById('generate-style').disabled = false;
        btn.classList.remove('loading')
    })
}

let html = ''
function generatePreview() {
    const konten = $('#konten').val();
    const style = $('#style').val();
    const files = $('[name=_files]').val()
    const btn = document.getElementById('preview');
    if (!konten) return alert('konten tidak boleh kosong!');

    btn.disabled = true;
    btn.classList.add('loading')
    fetch('{{ route('api.generate-preview') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'konten': konten,
            'style': style,
            'files': files
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log(data);
        html = data
        
        $('#html').val(data.html);
        updatePreview()
        // document.getElementById('html').value = data.html;
    })
    .catch(error => {
        console.error('Error:', error);
        $.toast({
            class: 'error',
            position: 'top center',
            message: `Terjadi Error, silahkan coba lagi !`
        })
    })
    .finally(() => {
        btn.disabled = false;
        btn.classList.remove('loading')
    })
}

function updatePreview() {
    // Mengambil value dari editor CodeMirror
    const code = $('#html').val();
    const previewFrame = document.getElementById('preview-frame');
    
    previewFrame.srcdoc = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>title</title>
    <script src='https://cdn.tailwindcss.com'></` + `script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined"rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round"rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp"rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Two+Tone"rel="stylesheet">
</head>

<body>
${code}

<footer class="bg-gray-800 text-gray-200 py-6">
    <div class="container mx-auto px-6 text-center">
        <p>&copy; 2025 {{ config('app.name') }}. All rights reserved.</p>
    </div>
</footer>
</body>

</html>`;
}
</script>
</x-volt-base>
