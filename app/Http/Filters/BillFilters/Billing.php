<?php

namespace App\Http\Filters\BillFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class Billing implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if($value)
            return $query->where('bill_number', null)
                ->orWhere('amount', null)
                ->orWhere('charge_account_id', null);
    }
}

?>
