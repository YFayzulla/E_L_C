<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The platform area: creating and suspending centres.
 *
 * A plain column check rather than `role:super-admin`, because Spatie roles are
 * per-centre once teams are on and this role belongs to no centre.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_super_admin, 403);

        return $next($request);
    }
}
