<?php

namespace App\Http\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class NumberBetween implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if(is_array($value)) {
            if($value[0] === '0')
                $query->where($property, '>', (float)$value[0]);
            else
                $query->where($property, '>=', (float)$value[0]);
            if($value[1])
                $query->where($property, '<', (float)$value[1]);
            return $query;
        } else {
            return $query->where($property, '>', $value);
        }
    }
}

