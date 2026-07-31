<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;

/**
 * Stamps `centre_id` onto the writes that bypass model events.
 *
 * `Model::upsert()` and `Model::insert()` build their rows as plain arrays and
 * go straight to the query builder — no `creating` event, so BelongsToCentre's
 * hook never runs and the column arrives NULL. There are six such call sites
 * today (bulk grading, skill grades, assessments) and there will be more.
 *
 * Doing it here rather than at each call site means a new bulk write cannot
 * quietly forget: the only way to write these tables is through this builder.
 */
class CentreBuilder extends Builder
{
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        return parent::upsert($this->stampCentre($values), $uniqueBy, $update);
    }

    public function insert(array $values)
    {
        return parent::insert($this->stampCentre($values));
    }

    public function insertOrIgnore(array $values)
    {
        return parent::insertOrIgnore($this->stampCentre($values));
    }

    /**
     * Accepts either one row or a list of rows, and never overwrites a value
     * that was set on purpose — the backfill and CreateCentreAction both write
     * rows for a centre other than the ambient one.
     */
    private function stampCentre(array $values): array
    {
        if ($values === []) {
            return $values;
        }

        $key = $this->getModel()->getCentreKeyName();

        // A single associative row, rather than a list of them.
        if (! is_array(reset($values))) {
            $values[$key] ??= app(CentreContext::class)->idOrFail();

            return $values;
        }

        $centreId = null;

        foreach ($values as $index => $row) {
            if (! is_array($row) || array_key_exists($key, $row)) {
                continue;
            }

            // Resolved once, and only if some row actually needs it — so a
            // fully-specified bulk write still works with no centre context.
            $centreId ??= app(CentreContext::class)->idOrFail();

            $values[$index][$key] = $centreId;
        }

        return $values;
    }
}
