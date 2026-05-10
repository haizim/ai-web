<?php

namespace App\Providers;

use App\Enums\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define(Permission::STYLES_VIEW, fn ($user) => $user->hasPermission(Permission::STYLES_VIEW));
        Gate::define(Permission::STYLES_MANAGE, fn ($user) => $user->hasPermission(Permission::STYLES_MANAGE));
    }
}
