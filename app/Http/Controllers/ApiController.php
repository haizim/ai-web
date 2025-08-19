<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\AiAgent;
use Illuminate\Http\Request;

class ApiController extends Controller
{
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
