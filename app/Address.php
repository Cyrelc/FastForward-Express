<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    public $primaryKey = "address_id";
    public $timestamps = false;

    protected $fillable = ['street', 'street2', 'city', 'zip_postal', 'state_province', 'country', 'is_primary', 'contact_id'];
}
