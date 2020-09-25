<?php

namespace App\Http\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filters database queries based on "between" two numbers:
 * Higher than the first, lower than the second.
 * Special case: If the second number is 0, ("lower than 0"), then instead checks whether the field is null
 */

class NumberBetween implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if(is_array($value)) {
            if($value[0] === '0')
                $query->where($property, '>', (float)$value[0]);
            else if($value[0])
                $query->where($property, '>=', (float)$value[0]);
            if($value[1] === '0')
                $query->whereNull($property);
            else if ($value[1])
                $query->where($property, '<', (float)$value[1]);
            return $query;
        } else {
            return $query->where($property, '>', $value);
        }
    }
}

