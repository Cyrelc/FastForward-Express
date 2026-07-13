<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Zone extends Model
{
    use LogsActivity;

    public $primaryKey = 'zone_id';

    protected $fillable = ['colour', 'coordinates', 'delivery_type_modifiers', 'inherits_coordinates_from', 'name', 'neighbours', 'ratesheet_id', 'tags'];
    public $timestamps = false;

    public function zoneGroups() {
        return $this->belongsToMany(ZoneGroup::class, 'zone_group_zone', 'zone_id', 'zone_group_id');
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

?>
