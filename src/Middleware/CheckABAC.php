<?php


namespace joey\abac\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CheckABAC
{

    public function handle($request, Closure $next, $slug, $access)
{
    if (!auth()->check() || !auth()->user()->account->is_active) {
        abort(403, 'Unauthorized');
    }

    if (!Gate::check('abac', [$slug, $access])) {
        abort(403);
    }

    return $next($request);
}
}
