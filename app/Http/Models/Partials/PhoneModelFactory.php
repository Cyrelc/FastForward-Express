<?php

namespace App\Http\Models\Partials;

use App\Http\Repos;

class PhoneModelFactory {
    public function GetCreateModel() {
        $phone = new \App\PhoneNumber();

        return $phone;
    }
}
