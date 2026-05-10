<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Style;
use Illuminate\Http\Request;

final class StyleService
{
    public static function save(Request $request): Style
    {
        return Style::create([
            'user_id' => $request->user()->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);
    }

    public static function get(int|string $id): Style
    {
        return Style::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);
    }

    public static function update(Request $request, int|string $id): Style
    {
        $style = Style::findOrFail($id);
        $style->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return $style;
    }

    public static function delete(int|string $id): void
    {
        Style::findOrFail($id)->delete();
    }
}
