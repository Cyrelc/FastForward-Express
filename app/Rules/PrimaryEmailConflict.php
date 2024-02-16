<?php

namespace App\Rules;

use App\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PrimaryEmailConflict implements ValidationRule
{
    protected $excludeUserId;

    public function __construct($excludeUserId = null) {
        $this->excludeUserId = $excludeUserId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        $primaryEmail = collect($value)->firstWhere('is_primary', true)['email'];

        $userExists = User::where('email', $primaryEmail)
            ->where('user_id', '!=', $this->excludeUserId)
            ->exists();

        if($userExists)
            $fail('Conflict with primary email address, please select another');
    }
}
