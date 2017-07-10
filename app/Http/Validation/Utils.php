<?php
namespace App\Http\Validation;


class Utils {
    public static function HasValue($input) {
        $hasValue = false;

        if ($input !== null)
            if (strlen((string)$input) > 0)
                $hasValue = true;

        return $hasValue;
    }
}
