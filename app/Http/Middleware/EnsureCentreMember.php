<?php

namespace App\Http\Middleware;

use App\Models\Centre;
use App\Tenancy\CentreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The signed-in person must actually belong to the centre they are looking at.
 *
 * The session cookie is shared across subdomains so that switching centres does
 * not mean logging in again. That cookie is *authentication* — it proves who
 * you are everywhere. It grants nothing: this check is what decides whether you
 * may be here, and it runs on every request.
 *
 * Deliberately a separate query from the role check. If membership and
 * permission were established by the same lookup, a bug in role assignment
 * would be a tenancy hole rather than a permissions bug.
 */
class EnsureCentreMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $centre = app(CentreContext::class)->centre();
        $user   = $request->user();

        if ($centre === null || $user === null) {
            return $next($request);
        }

        // The platform owner is not a member of anything and does not need to be.
        if ($user->is_super_admin) {
            return $next($request);
        }

        $member = $user->centres()
            ->whereKey($centre->id)
            ->wherePivot('status', Centre::MEMBER_ACTIVE)
            ->exists();

        if (! $member) {
            abort(403, 'Siz bu o‘quv markaziga biriktirilmagansiz.');
        }

        return $next($request);
    }
}
