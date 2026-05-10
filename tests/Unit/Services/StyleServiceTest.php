<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Style;
use App\Models\User;
use App\Services\StyleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can save a style', function (): void {
    $user = User::factory()->create();
    $request = Request::create('/', 'POST', [
        'name' => 'Test Style',
        'description' => 'Test Description',
    ]);
    $request->setUserResolver(fn () => $user);

    $style = StyleService::save($request);

    expect($style)->toBeInstanceOf(Style::class)
        ->and($style->name)->toBe('Test Style')
        ->and($style->description)->toBe('Test Description')
        ->and($style->user_id)->toBe($user->id);
});

it('can get a style by id', function (): void {
    $user = User::factory()->create();
    $style = Style::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $found = StyleService::get($style->id);

    expect($found->id)->toBe($style->id);
});

it('cannot get style belonging to another user', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $style = Style::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2);

    expect(fn () => StyleService::get($style->id))
        ->toThrow(ModelNotFoundException::class);
});

it('can update a style', function (): void {
    $user = User::factory()->create();
    $style = Style::factory()->create(['user_id' => $user->id]);
    $request = Request::create('/', 'PUT', [
        'name' => 'Updated Style',
        'description' => 'Updated Description',
    ]);

    StyleService::update($request, $style->id);

    $style->refresh();
    expect($style->name)->toBe('Updated Style')
        ->and($style->description)->toBe('Updated Description');
});

it('can delete a style', function (): void {
    $user = User::factory()->create();
    $style = Style::factory()->create(['user_id' => $user->id]);

    StyleService::delete($style->id);

    $this->assertSoftDeleted('styles', ['id' => $style->id]);
});
