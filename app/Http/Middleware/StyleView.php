<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StyleView
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()->can(Permission::STYLES_VIEW)) {
            return $next($request);
        }

        return abort(401);
    }
}
