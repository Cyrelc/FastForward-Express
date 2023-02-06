<?php
namespace App\Http\Repos;

use App\AccountUser;
use App\User;
use App\UserSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserRepo
{
    public function AddUserToAccountUser($contactId, $userId) {
        $accountUser = \App\AccountUser::where('contact_id', $contactId)->first();

        $accountUser->user_id = $userId;

        $accountUser->save();
    }

    public function ChangePassword($userId, $password) {
        $old = $this->GetById($userId);

        $old->password = Hash::make($password);
        $old->save();
    }

    public function CountAccountUsers($accountId) {
        $count = \App\AccountUser::where('account_id', $accountId)->count();

        return $count;
    }

    public function DeleteAccountUser($contactId, $accountId) {
        $userId = AccountUser::where('contact_id', $contactId)
            ->where('account_id', $accountId)
            ->pluck('user_id');

        if(!$userId)
            abort(400, 'Unable to find a user with given credentials. Please try again or contact support');

        /**
         * Secondary full request for delete is required, otherwise it will delete all entries matching the primary key ('contact_id')
         */
        $accountUser = AccountUser::where('contact_id', $contactId)
            ->where('account_id', $accountId)
            ->delete();

        if(AccountUser::where('contact_id', $contactId)->count() == 0) {
            $settings = \App\UserSettings::where('user_id', $userId)->delete();
            $user = User::where('user_id', $userId)->delete();
            $user->delete();
            $contactRepo = new ContactRepo();
            $contactRepo->Delete($contactId);
        }
    }

    public function GetAccountUser($contactId, $accountId) {
        $accountUser = AccountUser::where('contact_id', $contactId)
            ->where('account_id', $accountId);

        return $accountUser->first();
    }

    // public function GetAccountUserIds ($account_id) {
    //     $query = \App\AccountUser::where('account_id', $account_id)
    //         ->leftJoin('email_addresses', 'email_addresses.contact_id', '=', 'account_users.contact_id')
    //         ->leftJoin('phone_numbers', 'phone_numbers.contact_id', '=', 'account_users.contact_id');
    //     $accountUsers['contact_ids'] = $query->pluck('account_users.contact_id');
    //     $accountUsers['user_ids'] = $query->pluck('user_id');
    //     $accountUsers['email_ids'] = $query->pluck('email_address_id');
    //     $accountUsers['phone_ids'] = $query->pluck('phone_number_id');

    //     return $accountUsers;
    // }

    public function GetAccountUsers($accountId) {
        $accountUsers = AccountUser::where('account_id', $accountId)
            ->leftJoin('contacts', 'account_users.contact_id', '=', 'contacts.contact_id')
            ->leftJoin('users', 'account_users.user_id', '=', 'users.user_id')
            ->leftJoin('email_addresses', function($join) {
                $join->on('account_users.contact_id', '=', 'email_addresses.contact_id')
                    ->where('email_addresses.is_primary', true);
            })
            ->leftJoin('phone_numbers', function($join) {
                $join->on('account_users.contact_id', '=', 'phone_numbers.contact_id')
                    ->where('phone_numbers.is_primary', true);
            })
            ->select(
                'account_users.contact_id',
                'users.user_id',
                DB::raw('concat(contacts.first_name, " ", contacts.last_name) as name'),
                'email_addresses.email as primary_email',
                'phone_numbers.phone_number as primary_phone',
                'contacts.position as position',
                'account_users.is_primary as is_primary',
                'users.is_enabled as enabled',
                DB::raw('(select count(*) from account_users where account_users.contact_id = contacts.contact_id) as belongs_to_count')
            );

        return $accountUsers->get();
    }

    public function GetById($userId) {
        $user = User::where('user_id', '=', $userId)->first();

        return $user;
    }

    public function GetSettings($userId) {
        return UserSettings::where('user_id', $userId)
            ->select('use_imperial_default')
            ->first();
    }

    public function GetUserByEmployeeId($employeeId) {
        $employee = \App\Employee::where('employee_id', $employeeId)->first();
        $user = \App\User::where('user_id', $employee->user_id)->first();

        return $user;
    }

    public function GetUserByPrimaryEmail($emailAddress) {
        $user = User::where('email', $emailAddress);

        return $user->first();
    }

    public function Insert($user) {
        $new = new User;
        $newUserSettings = new \App\UserSettings;

        $user = array_merge($user, array(
            'password' => Hash::make(Str::random(15)),
            'login_attempts' => 0,
            'remember_token' => null
        ));

        $new = $new->create($user);
        $newUserSettings->create(['user_id' => $new->user_id]);

        return $new;
    }

    public function InsertAccountUser($accountUser) {
        $new = new \App\AccountUser;

        $accountUserCount = AccountUser::where('account_id', $accountUser['account_id'])->count();
        if($accountUserCount > 0)
            $accountUser['is_primary'] = 0;

        $new = $new->create($accountUser);
        $settings = new \App\UserSettings;
        $settings->create(['user_id' => $new->user_id]);

        return $new;
    }

    public function LinkAccountUser($contactId, $accountId) {
        $userId = AccountUser::where('contact_id', $contactId)->pluck('user_id');

        $accountUserCount = AccountUser::where('account_id', $accountId)->count();
        $new = new \App\AccountUser;

        $new->create(['account_id' => $accountId, 'contact_id' => $contactId, 'is_primary' => $accountUserCount == 0, 'user_id' => $userId[0]]);

        return $new;
    }

    public function SetAccountUserAsPrimary($accountId, $contactId) {
        AccountUser::where('account_id', $accountId)
            ->where('contact_id', '!=', $contactId)
            ->update(['is_primary' => 0]);

        AccountUser::where('account_id', $accountId)
            ->where('contact_id', $contactId)
            ->update(['is_primary' => 1]);
    }

    public function StoreSettings($userId, $settings) {
        $userSettings = UserSettings::where('user_id', $userId)
            ->update($settings);
    }

    public function Update($user, $updatePermissions = false) {
        $old = $this->GetById($user['user_id']);

        // no support for usernames atm
        // $old->username = $user['username'];
        if($updatePermissions)
            $old->is_enabled = $user['is_enabled'];
        $old->email = $user['email'];

        $old->save();

        return $old;
    }
}
