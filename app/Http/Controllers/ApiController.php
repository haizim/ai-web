<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageRequest;
use App\Models\Page;
use App\Services\AiAgent;
use Illuminate\Http\Request;
use League\HTMLToMarkdown\HtmlConverter;

class ApiController extends Controller
{
    public function generate(PageRequest $request)
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
            'konten' => $konten,
            'images' => $images,
            'html' => $body,
        ];
        $page = Page::create($newPage);

        return response()->json(['id' => $page->id]);
    }

    public function regenerate($id)
    {
        $page = Page::find($id);
        $page->html = AiAgent::generatePage($page->konten, $page->style, $page->images);
        $page->save();
        return response()->json(['html' => $page->html]);
    }

    public function editPage(Request $request)
    {
        $html = $request->html;
        $command = $request->command;
        
        $body = AiAgent::editPage($html, $command);
        return response()->json(['html' => $body]);
    }
}
