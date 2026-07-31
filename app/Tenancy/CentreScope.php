<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Narrows every query on a tenant model to the current centre.
 *
 * The column is qualified with the table name on purpose: without it a query
 * that joins two scoped tables fails with "ambiguous column centre_id" instead
 * of filtering, and an ambiguity error is a much better outcome than a filter
 * that silently binds to the wrong table.
 */
class CentreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(CentreContext::class);

        if ($context->isUnscoped()) {
            return;
        }

        $builder->where(
            $model->getTable() . '.' . $model->getCentreKeyName(),
            $context->idOrFail()
        );
    }
}
