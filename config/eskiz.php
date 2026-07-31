<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Eskiz SMS gateway
    |--------------------------------------------------------------------------
    |
    | Nothing is ever sent until ESKIZ_EMAIL and ESKIZ_PASSWORD are present in
    | .env — MessageService::isConfigured() checks exactly that and returns
    | early, so no HTTP request leaves the app and no exception reaches the UI.
    |
    | Get the credentials from your Eskiz cabinet: https://my.eskiz.uz
    |
    */

    'email'    => env('ESKIZ_EMAIL', env('SMS_SERVICE_EMAIL')),
    'password' => env('ESKIZ_PASSWORD', env('SMS_SERVICE_PASSWORD')),

    'base_url' => rtrim(env('ESKIZ_BASE_URL', 'https://notify.eskiz.uz/api'), '/'),

    /*
    | Sender nickname. 4546 is Eskiz's shared test sender: it delivers ONLY
    | texts that Eskiz has pre-approved. Free-form messages need your own
    | nickname, approved in the cabinet — set ESKIZ_FROM once you have it.
    */
    'from' => env('ESKIZ_FROM', '4546'),

    /*
    | Master switch. Leave true; the credential check above is what actually
    | gates sending. Set false to silence SMS entirely without removing
    | credentials (e.g. on a staging copy of production data).
    */
    'enabled' => (bool) env('ESKIZ_ENABLED', true),

    /*
    | Dry run: everything is resolved and logged, but no request is sent.
    | Useful for rehearsing a bulk send. Writes to the `sms` log channel.
    */
    'dry_run' => (bool) env('ESKIZ_DRY_RUN', false),

    /*
    | Delivery reports. Eskiz POSTs the status here; the route is
    | `sms.callback` and is exempt from CSRF. Leave null to disable.
    | Must be publicly reachable — localhost will never receive anything.
    */
    'callback_url' => env('ESKIZ_CALLBACK_URL'),

    'timeout' => (int) env('ESKIZ_TIMEOUT', 15),

    /*
    | The token Eskiz issues is valid for ~30 days; it is cached a little under
    | that and refreshed automatically on a 401.
    */
    'token_cache_key' => 'eskiz.token',
    'token_ttl_days'  => (int) env('ESKIZ_TOKEN_TTL_DAYS', 25),

    /*
    | Numbers that must never receive a real SMS (test rows, placeholder data).
    | Stored normalised: 998XXXXXXXXX.
    */
    'blocklist' => array_filter(array_map('trim', explode(',', (string) env('ESKIZ_BLOCKLIST', '998999999999')))),

];
