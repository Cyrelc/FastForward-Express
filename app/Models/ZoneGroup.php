<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ZoneGroup extends Model
{
    use LogsActivity;

    public $primaryKey = 'zone_group_id';

    protected $fillable = ['ratesheet_id', 'name', 'colour', 'zone_rates'];

    public function zones() {
        return $this->belongsToMany(Zone::class, 'zone_group_zone', 'zone_group_id', 'zone_id');
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
