<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Password implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value) {
        if(strlen($value) < 20) {
            return preg_match("/(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*_?<>0-9])[a-zA-Z\d\w\W].{8,}/", $value);
        } else {
            return preg_match("/(?=.*[a-z])(?=.*[A-Z])[a-zA-Z\d\w\W].{8,}/", $value);
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Password is too weak. Please review the password requirements and try again';
    }
}
