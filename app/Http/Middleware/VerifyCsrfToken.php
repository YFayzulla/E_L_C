<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Eskiz posts delivery reports from its own servers and has no session,
        // so it cannot carry a CSRF token. The handler only logs the payload.
        'sms/callback',
    ];
}
