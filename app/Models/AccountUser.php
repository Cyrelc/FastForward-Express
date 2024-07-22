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
        return $this->hasOne('App\Account', 'account_id');
    }

    public function contact() {
        return $this->hasOne(Contact::class, 'contact_id');
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // overrides
    public function delete() {
        return AccountUser::where('contact_id', $this->contact_id)
            ->where('account_id', $this->account_id)
            ->delete();
    }
}
