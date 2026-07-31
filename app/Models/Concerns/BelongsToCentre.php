<?php

namespace App\Models\Concerns;

use App\Models\Centre;
use App\Tenancy\CentreBuilder;
use App\Tenancy\CentreContext;
use App\Tenancy\CentreReassignmentException;
use App\Tenancy\CentreScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as belonging to one o'quv markazi.
 *
 * Three things, and the last two matter as much as the first:
 *
 *  1. every query is narrowed to the current centre;
 *  2. every new row is stamped with it, so nothing can be created ownerless;
 *  3. the stamp can never be changed afterwards — moving a row between centres
 *     would orphan everything that references it.
 */
trait BelongsToCentre
{
    public static function bootBelongsToCentre(): void
    {
        static::addGlobalScope(new CentreScope);

        static::creating(function (Model $model) {
            $key = $model->getCentreKeyName();

            // An explicit value is respected: CreateCentreAction and the
            // backfill migration both need to write rows for a centre other
            // than the ambient one.
            if ($model->getAttribute($key) === null) {
                $model->setAttribute($key, app(CentreContext::class)->idOrFail());
            }
        });

        static::updating(function (Model $model) {
            if ($model->isDirty($model->getCentreKeyName())) {
                throw new CentreReassignmentException(static::class, $model->getKey());
            }
        });
    }

    /**
     * upsert() / insert() never fire model events, so the `creating` hook above
     * cannot help them. This builder stamps those writes instead.
     */
    public function newEloquentBuilder($query): CentreBuilder
    {
        return new CentreBuilder($query);
    }

    public function getCentreKeyName(): string
    {
        return 'centre_id';
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class, $this->getCentreKeyName());
    }
}
