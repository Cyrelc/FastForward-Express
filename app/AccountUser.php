<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountUser extends Model
{
    use LogsActivity;

    public $primaryKey = 'contact_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'contact_id', 'account_id', 'is_primary'];

    public function account() {
        return $this->hasOne('App\Account', 'account_id');
    }

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
