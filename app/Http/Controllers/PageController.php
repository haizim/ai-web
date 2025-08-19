<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\AiAgent;
use App\Services\Gemini;
use Illuminate\Http\Request;
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
    public function store(Request $request)
    {
        $konten = $request->konten;
        $converter = new HtmlConverter();
        $konten = str_replace("<figure>", "", $konten);
        $konten = str_replace("</figure>", "", $konten);
        $konten = $converter->convert($konten);
        $judul = $request->judul;
        $style = $request->style;
        $images = $request->_files;

        $body = AiAgent::generatePage($konten, $style, $images);

        $newPage = [
            'slug' => $request->slug,
            'judul' => $judul,
            'style' => $style,
            'konten' => $konten,
            'images' => $images,
            'html' => $body,
        ];
        $page = Page::create($newPage);

        return redirect()->route('page.edit', [$page->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $page = Page::find($id);
        return view('page.show', compact('page'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $page = Page::find($id);
        return view('page.edit', compact('page'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
