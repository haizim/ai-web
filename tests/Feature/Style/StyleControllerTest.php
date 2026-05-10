<?php

declare(strict_types=1);

namespace Tests\Feature\Style;

use App\Enums\Permission;
use App\Models\Style;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravolt\Platform\Models\Permission as LaravoltPermission;
use Laravolt\Platform\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->viewPermission = LaravoltPermission::firstOrCreate(['name' => Permission::STYLES_VIEW]);
    $this->managePermission = LaravoltPermission::firstOrCreate(['name' => Permission::STYLES_MANAGE]);

    $this->viewerRole = Role::firstOrCreate(['name' => 'StylesViewer']);
    $this->viewerRole->addPermission($this->viewPermission);

    $this->managerRole = Role::firstOrCreate(['name' => 'StylesManager']);
    $this->managerRole->addPermission($this->viewPermission);
    $this->managerRole->addPermission($this->managePermission);

    Gate::define(Permission::STYLES_VIEW, fn ($user) => $user->hasPermission(Permission::STYLES_VIEW));
    Gate::define(Permission::STYLES_MANAGE, fn ($user) => $user->hasPermission(Permission::STYLES_MANAGE));

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
});

it('can access styles index with view permission', function (): void {
    $this->user->assignRole($this->viewerRole);

    $this->actingAs($this->user)
        ->get(route('styles.index'))
        ->assertOk();
});

it('cannot access styles index without view permission', function (): void {
    $this->actingAs($this->user)
        ->get(route('styles.index'))
        ->assertStatus(401);
});

it('can access styles create with manage permission', function (): void {
    $this->user->assignRole($this->managerRole);

    $this->actingAs($this->user)
        ->get(route('styles.create'))
        ->assertOk();
});

it('can store a style', function (): void {
    $this->user->assignRole($this->managerRole);

    $this->actingAs($this->user)
        ->post(route('styles.store'), [
            'name' => 'Test Style',
            'description' => 'Test Description',
        ])
        ->assertRedirect(route('styles.index'));

    $this->assertDatabaseHas('styles', [
        'name' => 'Test Style',
        'description' => 'Test Description',
        'user_id' => $this->user->id,
    ]);
});

it('can display style detail', function (): void {
    $this->user->assignRole($this->viewerRole);
    $style = Style::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('styles.show', $style))
        ->assertOk()
        ->assertSee($style->name);
});

it('can display style edit page', function (): void {
    $this->user->assignRole($this->managerRole);
    $style = Style::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('styles.edit', $style))
        ->assertOk()
        ->assertSee($style->name);
});

it('can update a style', function (): void {
    $this->user->assignRole($this->managerRole);
    $style = Style::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put(route('styles.update', $style), [
            'name' => 'Updated Style',
            'description' => 'Updated Description',
        ])
        ->assertRedirect(route('styles.index'));

    $this->assertDatabaseHas('styles', [
        'id' => $style->id,
        'name' => 'Updated Style',
        'description' => 'Updated Description',
    ]);
});

it('can delete a style', function (): void {
    $this->user->assignRole($this->managerRole);
    $style = Style::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete(route('styles.destroy', $style))
        ->assertRedirect(route('styles.index'));

    $this->assertSoftDeleted('styles', ['id' => $style->id]);
});
