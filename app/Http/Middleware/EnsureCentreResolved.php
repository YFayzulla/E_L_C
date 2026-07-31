<?php

namespace App\Http\Middleware;

use App\Tenancy\CentreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses a request that reached a centre route without a centre.
 *
 * The global scope already throws rather than returning nothing, so this is
 * belt and braces — but it turns "500, MissingCentreContextException" into a
 * redirect to the centre picker, which is what the situation actually is:
 * you asked for a centre page on the apex domain.
 */
class EnsureCentreResolved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(CentreContext::class)->has()) {
            if ($request->expectsJson()) {
                abort(400, 'O‘quv markazi ko‘rsatilmagan.');
            }

            return redirect()->route('centres.choose');
        }

        return $next($request);
    }
}
