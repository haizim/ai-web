<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Style;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravolt\Suitable\AutoFilter;
use Laravolt\Suitable\AutoSearch;
use Laravolt\Suitable\AutoSort;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('has correct fillable attributes', function (): void {
    $style = new Style;

    expect($style->getFillable())
        ->toContain('name')
        ->toContain('description')
        ->toContain('user_id');
});

it('uses soft deletes', function (): void {
    expect(in_array(SoftDeletes::class, class_uses_recursive(Style::class)))->toBeTrue();
});

it('uses auto filter, search, and sort traits', function (): void {
    expect(in_array(AutoFilter::class, class_uses_recursive(Style::class)))->toBeTrue();
    expect(in_array(AutoSearch::class, class_uses_recursive(Style::class)))->toBeTrue();
    expect(in_array(AutoSort::class, class_uses_recursive(Style::class)))->toBeTrue();
});

it('belongs to user', function (): void {
    $user = User::factory()->create();
    $style = Style::factory()->create(['user_id' => $user->id]);

    expect($style->user)->toBeInstanceOf(User::class);
    expect($style->user->id)->toBe($user->id);
});
