<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;

class StreamingAvailabilityFilter
{
    public static function apply(Builder $query, array $selected): Builder
    {
        $columns = [];
        foreach (config('catalogues.services') as $key => $service) {
            if (($selected[$key] ?? 0) == 1) {
                $columns[] = $service['table'].'.'.$service['column'];
            }
        }

        if ($columns) {
            $query->where(function (Builder $streaming) use ($columns) {
                foreach ($columns as $column) {
                    $streaming->orWhere($column, '=', 1);
                }
            });
        }

        return $query;
    }
}
