<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // Wildcard subdomen ishlatilgani uchun Host sarlavhasiga ishonib
        // bo'lmaydi — ResolveCentre aynan shundan markazni aniqlaydi.
        // `local` muhitda va testlarda Laravel buni o'zi o'chirib qo'yadi.
        \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            // FIRST, and before SubstituteBindings: route-model binding must
            // resolve inside the centre context or `edit(Group $group)` would
            // happily load another centre's group. Also before the `role:`
            // middleware, which needs Spatie's team id already set.
            \App\Http\Middleware\ResolveCentre::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // LAST, because it needs the session guard. Applied to the whole
            // group rather than to each route group in web.php so that a route
            // added later cannot quietly miss it. It is a no-op for guests.
            \App\Http\Middleware\EnsureCentreMember::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\ResolveCentre::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        // Config-gated, role-aware variant. Unlike 'verified' it never blocks an
        // account that has no e-mail address, and it is inert until
        // REQUIRE_EMAIL_VERIFICATION=true. See config/grading.php.
        'verified.role' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        // Tenancy. `centre.member` runs after `auth` and is what the shared
        // session cookie is gated on: the cookie proves who you are on every
        // subdomain, this decides whether you may be on this one.
        'centre'        => \App\Http\Middleware\EnsureCentreResolved::class,
        'centre.member' => \App\Http\Middleware\EnsureCentreMember::class,
        'super-admin'   => \App\Http\Middleware\EnsureSuperAdmin::class,
        'centre.token'  => \App\Http\Middleware\EnsureTokenForCentre::class,

        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,


    ];
}
