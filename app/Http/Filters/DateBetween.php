<?php

namespace App\Http\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DateBetween implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if(is_array($value)) {
            return $query->whereBetween($property, $value);
        } else {
            return $query->whereDate($property, '>=', $value);
        }
    }
}
?>
