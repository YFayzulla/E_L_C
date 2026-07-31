<?php

namespace App\Tenancy;

use App\Models\Centre;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * Which centre the current request, command or job belongs to.
 *
 * Registered as a singleton, so `app(CentreContext::class)` is the one place
 * that knows the answer. Everything else — the global scope, the middleware,
 * the console loops — goes through here.
 */
class CentreContext
{
    private ?Centre $centre = null;

    /** Set by withoutScope(): a deliberate, visible cross-centre read. */
    private bool $unscoped = false;

    public function centre(): ?Centre
    {
        return $this->centre;
    }

    public function id(): ?int
    {
        return $this->centre?->id;
    }

    public function has(): bool
    {
        return $this->centre !== null;
    }

    public function isUnscoped(): bool
    {
        return $this->unscoped;
    }

    /**
     * The id a query must filter by, or an exception explaining that nobody
     * said which centre this is.
     */
    public function idOrFail(): int
    {
        if ($this->centre === null) {
            throw new MissingCentreContextException();
        }

        return (int) $this->centre->id;
    }

    /**
     * Point the whole process at a centre.
     *
     * Two things have to happen together, and forgetting either is a silent
     * bug rather than a loud one:
     *
     *  - Spatie's team id, or every role check answers for the wrong centre;
     *  - the loaded `roles` relation is dropped, because it is an ordinary
     *    Eloquent relation and would otherwise keep answering for the centre
     *    it was loaded under. forgetCachedPermissions() does NOT do this — it
     *    caches role *definitions*, not the pivot rows.
     */
    public function set(?Centre $centre): void
    {
        $this->centre = $centre;

        setPermissionsTeamId($centre?->id);

        $user = app(AuthFactory::class)->guard()->user();

        if ($user !== null && method_exists($user, 'unsetRelation')) {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
    }

    public function forget(): void
    {
        $this->set(null);
    }

    /**
     * Run a callback as a given centre, then put back whatever was there.
     *
     * @param  \App\Models\Centre|int|string  $centre
     */
    public function for($centre, callable $callback)
    {
        $resolved = $centre instanceof Centre
            ? $centre
            : Centre::withoutGlobalScopes()
                ->when(is_numeric($centre),
                    fn($q) => $q->whereKey($centre),
                    fn($q) => $q->where('slug', $centre))
                ->firstOrFail();

        $previous = $this->centre;

        $this->set($resolved);

        try {
            return $callback($resolved);
        } finally {
            $this->set($previous);
        }
    }

    /**
     * Run a callback with the tenant filter switched off.
     *
     * Every call site is a place where data crosses centres on purpose, so
     * keep them few and grep-able.
     */
    public function withoutScope(callable $callback)
    {
        $previous = $this->unscoped;
        $this->unscoped = true;

        try {
            return $callback();
        } finally {
            $this->unscoped = $previous;
        }
    }

    /**
     * Run a callback once per active centre.
     *
     * This is how every console command must sweep the installation — without
     * it, a command runs with no team id and Spatie answers "no roles at all",
     * so `User::role('student')` matches nobody and the command reports
     * success having done nothing.
     */
    public function each(callable $callback): void
    {
        $centres = $this->withoutScope(
            fn() => Centre::query()->active()->orderBy('id')->get()
        );

        foreach ($centres as $centre) {
            $this->for($centre, $callback);
        }
    }
}
