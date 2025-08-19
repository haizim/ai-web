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
    height: 95vh;
    width: 100%;
    /* background-color: #eee; */
}
</style>

<div x-data="editPage()">
    <div style="height: 5vh; text-align: center" class="p-1">
        <div class="ui icon buttons">
            <button type="button" class="ui black button" @click="setSize('zero', 'sixteen')"><i class="expand icon"></i></button>
            <button type="button" class="ui black button" @click="setSize('eight', 'eight')"><i class="columns icon"></i></button>
            <button type="button" class="ui black button" @click="setSize('twelve', 'four')"><i class="mobile icon"></i></button>
        </div>
    </div>
    <div class="ui grid">
        <div :class="code_size + ' wide column p-r-0 p-b-0'" :style="code_size == 'zero' ? 'display: none' : ''">
            <textarea id="editor" name="html">{!! $page->html !!}</textarea>
        </div>

        <div :class="preview_size + ' wide column p-l-0 p-b-0'">
            <iframe id="preview-frame" style="width: 100%; height: 95vh; border: 2px solid #888" sandbox="allow-scripts">
            </iframe>
        </div>
    </div>
</div>

<script>
var editor = CodeMirror.fromTextArea(document.getElementById("editor"), {
    lineNumbers: true,
    mode: 'htmlmixed',
    // theme: 'material-darker',
    lineWrapping: true
});

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
</script>
</x-volt-base>