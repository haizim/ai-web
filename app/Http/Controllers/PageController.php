<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageRequest;
use App\Models\Page;
use App\Services\AiAgent;
use App\Services\Gemini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\HTMLToMarkdown\HtmlConverter;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('page.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('page.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PageRequest $request)
    {
        $data = $request->validated();
        $data['images'] = $request['_files'];
        unset($data['_files']);
        
        $page = Page::create($data);

        // return redirect()->route('page.edit', [$page->id]);
        return redirect()->route('page.index')->withSuccess('Halaman berhasil dibuat');
    }

    public function store_old(PageRequest $request)
    {
        $konten = $request->konten;
        $converter = new HtmlConverter();
        $konten = str_replace("<figure>", "", $konten);
        $konten = str_replace("</figure>", "", $konten);
        $konten = $converter->convert($konten);
        $judul = $request->judul;
        $style = $request->style;
        $images = json_decode($request->_files, true);

        $body = AiAgent::generatePage($konten, $style, $images);

        $newPage = [
            'slug' => $request->slug,
            'judul' => $judul,
            'style' => $style,
            'konten' => $request->konten,
            'images' => $images,
            'html' => $body,
            'user_id' => auth()->user()->id,
        ];
        $page = Page::create($newPage);

        return redirect()->route('page.edit', [$page->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $page = Page::where('slug', $id)
            ->where('status', 'AKTIF')
            ->firstOrFail();
        return view('page.show', compact('page'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $page = Page::find($id);
        if ($page->user_id !== auth()->user()->id) {
            abort(403);
        }
        return view('page.edit', compact('page'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Page::find($id)->update([
            'html' => $request->html,
            'judul' => $request->judul,
            'slug' => $request->slug,
        ]);

        // return redirect()->route('page.index')->withSuccess('Halaman berhasil diperbarui');
        return redirect()->route('page.index')->withSuccess('Halaman berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Page::where('id', $id)->update([
            'status' => DB::raw("case when status = 'AKTIF' then 'DRFT' else 'AKTIF' end")
        ]);

        return redirect()->route('page.index')->withSuccess('Halaman berhasil dihapus');
    }
}
