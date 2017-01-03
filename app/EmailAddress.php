<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailAddress extends Model
{
    public $primaryKey = "email_address_id";
    public $timestamps = false;

    protected $fillable = ['email', 'is_primary', 'contact_id'];
}
