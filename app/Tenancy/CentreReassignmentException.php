<?php

namespace App\Tenancy;

use RuntimeException;

/**
 * Thrown when something tries to move an existing row to a different centre.
 *
 * There is no legitimate reason to. A group carries attendance, homework,
 * skill grades and certificates; changing its centre_id would leave every one
 * of those pointing at data the new centre cannot see and the old one no
 * longer owns. If a centre really needs to be merged into another, that is a
 * deliberate migration, not an ->update().
 */
class CentreReassignmentException extends RuntimeException
{
    public function __construct(string $model, $key)
    {
        parent::__construct(
            "{$model}#{$key}: mavjud yozuvni boshqa o‘quv markaziga ko‘chirib "
            . 'bo‘lmaydi — unga bog‘langan barcha ma‘lumot yetim qolardi.'
        );
    }
}
