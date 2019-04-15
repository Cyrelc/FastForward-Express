<?php

namespace App\Http\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DateBetween implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        return $query->where(function($temp) use ($value, $property) {
            if(is_array($value)) {
                if($value[0] != '')
                    $temp->whereDate($property, '>=', $value[0]);
                if($value[1] != '')
                    $temp->whereDate($property, '<=', $value[1]);
            } else 
                $temp->whereDate($property, '>=', $value);
        });
    }
}
?>
