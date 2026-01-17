<x-volt-base title="Edit Halaman">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/theme/material-darker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/edit/closetag.min.js"></script>
<style>
.CodeMirror {
    border:  2px solid #888;
    height: 80vh;
    width: 100%;
    /* background-color: #eee; */
}
</style>

{!! form()->put()->action(route('page.update', $page->id))->id('form') !!}
    
<div x-data="editPage()" style="height: 100vh; overflow: hidden;">
    <div class="ui grid">
        <div :class="code_size + ' wide column p-r-0 p-b-0'" :style="code_size == 'zero' ? 'display: none' : ''">
            <textarea id="editor" name="html">{!! $page->html !!}</textarea>

            <div class="ui fluid input">
                <textarea id="command" placeholder="Apa yang ingin diubah?" style="height: 11.5vh; width: 100%;"></textarea>
            </div>
            <button type="button" class="ui icon black fluid bottom attached button" onclick="send_command()"><i class="robot icon"></i></button>
        </div>

        <div :class="preview_size + ' wide column p-l-0 p-b-0'">
            <iframe id="preview-frame" style="width: 100%; height: 95vh; border: 2px solid #888" sandbox="allow-scripts">
            </iframe>
        </div>
    </div>

    <div style="height: 5vh; text-align: right" class="p-2">
        <div x-data="{ judul: '{{ $page->judul }}'}" class="field">
            <div class="three fields">
                <div class="field">
                    <input type="text" name="judul" placeholder="Judul" x-model="judul" required>
                </div>
                <div class="field">
                    <div class="ui fluid labeled input m-b-1">
                        <div class="ui black label">
                            {{ config('app.url') }}/p/
                        </div>
                        <input type="text" name="slug" value="{{ $page->slug }}" placeholder="URL(mis: nama-halaman)" :value="judul.replaceAll(/[^a-zA-Z0-9 ]/g, ' ').replaceAll(/\s+/g, ' ').replaceAll(' ', '-').toLowerCase()" required>
                    </div>
                </div>
                <div class="field">
                    <div class="ui icon buttons">
                        <button type="button" class="ui black button" @click="setSize('zero', 'sixteen')"><i class="expand icon"></i></button>
                        <button type="button" class="ui black button" @click="setSize('eight', 'eight')"><i class="columns icon"></i></button>
                        <button type="button" class="ui black button" @click="setSize('twelve', 'four')"><i class="mobile icon"></i></button>
                    </div>
            
                    <div class="ui icon buttons">
                        <button type="button" class="ui black button" onclick="$('#mdl-edit').modal('show')"><i class="pencil icon"></i></button>
                        <button type="button" class="ui black button" onclick="generatePreview()" id="generate-preview"><i class="undo icon"></i></button>
                        <button type="submit" class="ui black button"><i class="save icon"></i></button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<textarea name="konten" id="konten" style="display: none">{{ $page->konten }}</textarea>
<textarea name="style" id="style" style="display: none">{{ $page->style }}</textarea>
{!! form()->close() !!}

<div class="ui modal" id="mdl-edit">
    <div class="header">Konten</div>
    <div class="scrolling content">
        <div class="ui form">

            <div style="display: none">
                {!! form()->redactor('k', $page->konten)->id('ek')->placeholder('Konten')->required()->label('Konten') !!}
            </div>
            
            {!! form()->textarea('konten', $page->konten)->id('edit-konten')->placeholder('Konten')->required()->label('Konten') !!}

            {!! form()->textarea('style', $page->style)->id('edit-style')->placeholder('Deskripsi Tampilan')->required()->class('m-b-1')->label('Deskripsi Tampilan') !!}

            <div style="width: 100%;text-align: right;margin-top: -5em;padding-right: .5em; margin-bottom: 1em;">
                <button id="generate-style" type="button" class="ui black icon button" onclick="generateStyle()">
                    <i class="robot icon"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="actions">
        <div class="ui black deny button">
            <i class="remove icon"></i>
            Tutup
        </div>
    </div>
</div>

<div class="ui basic modal" id="loading-modal">
    <div class="ui icon header">
        <i class="asterisk loading icon"></i>
    </div>
</div>

<script>
let reditor = Redactor('#edit-konten');
var editor = CodeMirror.fromTextArea(document.getElementById("editor"), {
    lineNumbers: true,
    mode: 'htmlmixed',
    // theme: 'material-darker',
    lineWrapping: true
});

const page_id = {{ $page->id }};

$('#loading-modal').modal({
    closable: false
})

const previewFrame = document.getElementById('preview-frame');

function updatePreview() {
    // Mengambil value dari editor CodeMirror
    const code = editor.getValue();
    
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

// Mengatur agar preview di-update setiap kali ada perubahan di editor
editor.on('change', updatePreview);

// Panggil fungsi sekali saat halaman dimuat untuk menampilkan pratinjau awal
window.onload = updatePreview;

$('#mdl-edit').modal({
    // Fungsi ini akan berjalan setelah modal tertutup
    onHidden: function() {
        $('#style').val($('#edit-style').val())
        $('#konten').val($('#edit-konten').val())
    }
});

function editPage() {
    return {
        'title': 'Edit Halaman',
        'html': `{!! $page->html !!}`,
        'code_size': 'eight',
        'preview_size': 'eight',
        
        setSize(code, preview) {
            this.code_size = code;
            this.preview_size = preview;
        }
    }
}

function chat() {
    return {
        'chats': [],
        'token': 0,
        'judul': '',
        'show': false,
    }
}

function send_command() {
    const command = document.getElementById('command').value;
    const code = editor.getValue();

    const data_send = {
        'html': code,
        'command': command
    }

    editor.options.readOnly = true
    $('#loading-modal').modal('show')

    fetch('{{ route('api.edit-page') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data_send)
    })
    .then(response => response.json())
    .then(data => {
        editor.setValue(data.html);
    })
    .finally(() => {
        editor.options.readOnly = false
        $('#loading-modal').modal('hide')
    });
    
    document.getElementById('command').value = '';
    document.getElementById('command').focus();
}

document.addEventListener('keydown', (event) => {
    if(event.ctrlKey && event.key == "Enter") {
        send_command()
    }
});

function generatePreview() {
    if (!confirm('Yakin ingin regenerate halaman? Perubahan yang sudah ada akan hilang')) return false;
    editor.options.readOnly = true
    $('#loading-modal').modal('show')

    const konten = $('#konten').val();
    const style = $('#style').val();
    const files = $('[name=_files]').val()
    const btn = document.getElementById('generate-preview');
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
        
        // $('#html').val(data.html);
        editor.setValue(data.html);
        // updatePreview()
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
        editor.options.readOnly = false
        $('#loading-modal').modal('hide')
        btn.disabled = false;
        btn.classList.remove('loading')
    })
}

function generateStyle() {
    const konten = document.getElementById('edit-konten').value;
    const btn = document.getElementById('generate-style');
    const style = $('#edit-style').val();
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
        document.getElementById('edit-style').value = data.style;
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
</script>
</x-volt-base>