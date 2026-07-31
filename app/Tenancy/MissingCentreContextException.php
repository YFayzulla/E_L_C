<?php

namespace App\Tenancy;

use RuntimeException;

/**
 * Thrown when a tenant-scoped query runs with no centre selected.
 *
 * Deliberately loud. The alternative — quietly returning nothing — is
 * indistinguishable from "this centre is genuinely empty", which is how you
 * get a monthly billing command that decrements nobody's debt counter and
 * reports success for a year.
 *
 * If you meant to read across centres, say so: Centre::withoutScope(...).
 * If you meant a particular one, say which: Centre::for($slug, ...).
 */
class MissingCentreContextException extends RuntimeException
{
    public function __construct(string $message = null)
    {
        parent::__construct($message ?? implode(' ', [
            'Joriy o‘quv markazi aniqlanmagan.',
            'HTTP so‘rovda buni ResolveCentre middleware qiladi;',
            'konsolda Centre::each() yoki Centre::for() ishlating,',
            'markazlar bo‘ylab o‘qish kerak bo‘lsa — Centre::withoutScope().',
        ]));
    }
}
