<?php

namespace App\Http\Controllers;

use App\Http\Requests\MiniAppRequest;
use App\Models\MiniApp;
use App\Services\AiAgent;
use Illuminate\Http\Request;
use League\HTMLToMarkdown\HtmlConverter;

class ApiMiniAppController extends Controller
{
    public function generate(MiniAppRequest $request)
    {
        $konten = $request->konten;
        $converter = new HtmlConverter();
        $konten = str_replace("<figure>", "", $konten);
        $konten = str_replace("</figure>", "", $konten);
        $konten = $converter->convert($konten);
        $judul = $request->judul;
        $style = $request->style;
        $functionality = $request->functionality ?? '';
        $images = json_decode($request->_files, true);

        $body = AiAgent::generateMiniApp($konten, $functionality, $style, $images);

        $newMiniApp = [
            'slug' => $request->slug,
            'judul' => $judul,
            'deskripsi' => $request->deskripsi ?? null,
            'style' => $style,
            'functionality' => $functionality,
            'konten' => $konten,
            'images' => $images,
            'html' => $body,
        ];
        $miniApp = MiniApp::create($newMiniApp);

        return response()->json(['id' => $miniApp->id]);
    }

    public function regenerate($id)
    {
        $miniApp = MiniApp::find($id);
        $html = AiAgent::generateMiniApp($miniApp->konten, $miniApp->functionality, $miniApp->style, $miniApp->images);
        
        return response()->json(['html' => $html]);
    }

    public function editMiniApp(Request $request)
    {
        $html = $request->html;
        $command = $request->command;
        
        $body = AiAgent::editMiniApp($html, $command);
        return response()->json(['html' => $body]);
    }

    public function generateFunctionality(Request $request)
    {
        $converter = new HtmlConverter();
        $konten = $request->konten;
        $konten = $converter->convert($konten);
        
        $body = AiAgent::generateFunctionality($konten);
        return response()->json(['functionality' => $body]);
    }

    public function generatePreview(Request $request)
    {
        $converter = new HtmlConverter();
        $konten = $request->konten;
        $konten = $converter->convert($konten);
        
        $body = AiAgent::generateMiniApp($konten, $request->functionality ?? '', $request->style, $request->files);
        return response()->json(['html' => $body]);
    }

    public function generateMiniAppStyle(Request $request)
    {
        $converter = new HtmlConverter();
        $functionality = $request->functionality;
        
        if (empty($functionality)) {
            $konten = $request->konten;
            $konten = $converter->convert($konten);
            
            // First generate functionality if not provided
            $functionality = AiAgent::generateFunctionality($konten);
        }
        
        $style = $request->style ?? null;
        
        $body = AiAgent::generateMiniAppStyle($functionality, $style);
        return response()->json(['style' => $body]);
    }
}