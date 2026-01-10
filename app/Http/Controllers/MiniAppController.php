<?php

namespace App\Http\Controllers;

use App\Http\Requests\MiniAppRequest;
use App\Models\MiniApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MiniAppController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('miniapp.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('miniapp.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MiniAppRequest $request)
    {
        $data = $request->validated();
        $data['images'] = $request->_files;
        $data['user_id'] = auth()->user()->id;
        unset($data['_files']);
        
        $miniApp = MiniApp::create($data);

        return redirect()->route('miniapp.edit', [$miniApp->id])->withSuccess('Mini App berhasil dibuat');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $miniApp = MiniApp::where('slug', $id)
            ->where('status', 'AKTIF')
            ->firstOrFail();
        return view('miniapp.show', compact('miniApp'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $miniApp = MiniApp::find($id);
        if ($miniApp->user_id !== auth()->user()->id) {
            abort(403);
        }
        return view('miniapp.edit', compact('miniApp'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        MiniApp::find($id)->update([
            'html' => $request->html,
            'judul' => $request->judul,
            'slug' => $request->slug,
        ]);

        return redirect()->route('miniapp.index')->withSuccess('Mini App berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        MiniApp::where('id', $id)->update([
            'status' => DB::raw("case when status = 'AKTIF' then 'DRFT' else 'AKTIF' end")
        ]);

        return redirect()->route('miniapp.index')->withSuccess('Mini App berhasil dihapus');
    }
}