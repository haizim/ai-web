<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StyleRequest;
use App\Models\Style;
use App\Services\StyleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class StyleController
{
    public function index(): View
    {
        return view('style.index');
    }

    public function create(): View
    {
        return view('style.create');
    }

    public function store(StyleRequest $request): RedirectResponse
    {
        StyleService::save($request);

        return redirect()->route('styles.index')->withSuccess('Style berhasil dibuat.');
    }

    public function show(string $id): View
    {
        $style = StyleService::get($id);

        return view('style.show', compact('style'));
    }

    public function edit(string $id): View
    {
        $style = StyleService::get($id);

        return view('style.edit', compact('style'));
    }

    public function update(StyleRequest $request, string $id): RedirectResponse
    {
        StyleService::update($request, $id);

        return redirect()->route('styles.index')->withSuccess('Style berhasil diperbarui.');
    }

    public function destroy(Style $style): RedirectResponse
    {
        StyleService::delete((string) $style->id);

        return redirect()->route('styles.index')->withSuccess('Style berhasil dihapus.');
    }

    public function list(): JsonResponse
    {
        if (! auth()->user()) {
            return response()->json([], 401);
        }

        $styles = Style::query()
            ->where('user_id', auth()->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json($styles);
    }
}
