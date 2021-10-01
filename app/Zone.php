<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Zone extends Model
{
    use LogsActivity;

    public $primaryKey = 'zone_id';

    protected $fillable = ['additional_costs', 'additional_time', 'coordinates', 'inherits_coordinates_from', 'name', 'neighbours', 'ratesheet_id', 'type'];
    public $timestamps = false;

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}

?>
