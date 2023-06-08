<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CreditCard extends Model {
    use LogsActivity;

    public $primaryKey = 'credit_card_id';
    public $timestamps = true;

    protected $fillable = [
        'account_id',
        'data_key'
    ];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }
}

?>
