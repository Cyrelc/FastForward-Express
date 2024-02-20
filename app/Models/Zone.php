<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Zone extends Model
{
    use LogsActivity;

    public $primaryKey = 'zone_id';

    protected $fillable = ['additional_costs', 'additional_time', 'coordinates', 'inherits_coordinates_from', 'name', 'neighbours', 'ratesheet_id', 'type'];
    public $timestamps = false;

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

?>
