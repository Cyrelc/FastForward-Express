<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Address extends Model
{
    use LogsActivity;

    public $primaryKey = "address_id";
    public $timestamps = false;

    protected $fillable = ['name', 'street', 'street2', 'city', 'zip_postal', 'state_province', 'country', 'is_primary', 'contact_id', 'lat', 'lng', 'formatted'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
