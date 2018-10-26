<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class EmailAddress extends Model
{
    use LogsActivity;

    public $primaryKey = "email_address_id";
    public $timestamps = false;

    protected $fillable = ['email', 'is_primary', 'contact_id'];

    protected static $logFillable = true;
}
