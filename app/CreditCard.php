<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class CreditCard extends Model {
    use LogsActivity;

    public $primaryKey = 'credit_card_id';
    public $timestamps = true;

    protected $fillable = [
        'account_id',
        'data_key'
    ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function account() {
        return $this->belongsTo(Account::class);
    }
}

?>
