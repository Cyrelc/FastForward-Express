<?php

namespace App\Models;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AccountUser extends Model {
    use LogsActivity;

    public $primaryKey = 'contact_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'contact_id', 'account_id', 'is_primary'];

    public function account() {
        return $this->hasOne(Account::class, 'account_id');
    }

    public function contact() {
        return $this->hasOne(Contact::class, 'contact_id');
    }

    public function user() {
        return $this->hasOne(User::class);
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
