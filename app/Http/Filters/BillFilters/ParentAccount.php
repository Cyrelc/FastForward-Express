<?php

namespace App\Http\Filters\BillFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Bills charged to the given parent account(s) or to any of their child accounts.
 * Expects the bills list query, where the charged account is joined as "charge_account".
 */
class ParentAccount implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        $accountIds = is_array($value) ? $value : [$value];

        // Grouped so the OR can't escape the other filters or the user's account restrictions
        return $query->where(function($query) use ($accountIds) {
            $query->whereIn('charge_account.parent_account_id', $accountIds)
                ->orWhereIn('charge_account.account_id', $accountIds);
        });
    }
}

?>
