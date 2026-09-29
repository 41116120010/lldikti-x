<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Shared half-open date-range filtering.
 *
 * Replacing `whereDate()` matters for performance: `DATE(col) >= ?` wraps the
 * column in a function, which no database can satisfy with an index. Every
 * index on the column becomes dead and the query degrades to a full scan plus
 * a filesort. Comparing raw timestamps keeps the index usable.
 */
trait FiltersByDateRange
{
    /**
     * Apply an inclusive calendar-day range to a timestamp column.
     *
     * The end bound is written as `< next-day-midnight` (a half-open interval)
     * rather than `<= end-of-day`, so the whole final day is included without
     * having to build a 23:59:59 literal.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $column  Timestamp column, e.g. `waktu_mulai`.
     * @param  string|null  $start  Inclusive start date (Y-m-d).
     * @param  string|null  $end  Inclusive end date (Y-m-d).
     */
    protected function applyDateRange(Builder $query, string $column, ?string $start, ?string $end): Builder
    {
        $startOfRange = $this->parseDateBoundary($start);
        $endOfRange = $this->parseDateBoundary($end);

        if ($startOfRange !== null) {
            $query->where($column, '>=', $startOfRange);
        }

        if ($endOfRange !== null) {
            $query->where($column, '<', $endOfRange->copy()->addDay()->startOfDay());
        }

        return $query;
    }

    /**
     * Parse a user-supplied `Y-m-d` value, or return null when absent/unusable.
     *
     * `Carbon::parse()` throws on garbage, and these values arrive straight from
     * query strings, so an unparsable bound is treated as "no bound" rather than
     * allowed to surface as a 500.
     */
    private function parseDateBoundary(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
