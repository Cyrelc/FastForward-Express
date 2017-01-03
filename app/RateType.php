<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RateType extends Model
{
    public $primaryKey = "rate_type_id";
    public $timestamps = false;

    public function Accounts() {
        return $this->hasMany('App\Account');
    }
}
