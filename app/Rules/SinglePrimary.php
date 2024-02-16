<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SinglePrimary implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail) : void {
        $primaryCount = collect($value)->reduce(function ($carry, $item) {
            return $carry + (filter_var($item['is_primary'], FILTER_VALIDATE_BOOLEAN) === true ? 1 : 0);
        }, 0);

        // Pass the rule only if 'is_primary' is true for exactly one item
        if($primaryCount !== 1)
            $fail('There can only be one primary :attribute');
    }
}
