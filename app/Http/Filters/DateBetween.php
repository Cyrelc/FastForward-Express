<?php

namespace App\Http\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DateBetween implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if(is_array($value)) {
            if($value[0])
                $query->whereDate($property, '>=', $value[0]);
            if($value[1])
                $query->whereDate($property, '<=', $value[1]);
            return $query;
        } else
            return $query->whereDate($property, '>=', $value);
    }
}
?>
