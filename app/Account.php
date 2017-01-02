<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public $primaryKey = "account_id";
    public $timestamps = false;

    public function contacts() {
        return $this->belongsToMany('App\Contact', 'account_contacts');
    }
}
