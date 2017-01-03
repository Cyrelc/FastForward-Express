<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhoneNumber extends Model
{
    public $primaryKey = "phone_number_id";
    public $timestamps = false;

    protected $fillable = ['phone_number', 'is_primary', 'type', 'contact_id'];
}
