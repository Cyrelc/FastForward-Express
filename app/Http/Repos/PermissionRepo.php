<?php
namespace App\Http\Repos;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\User;

class PermissionRepo {

    public function assignUserPermissions($user, $permissions) {
        $permissionsChanged = [];

        foreach($permissions as $name => $value) {
            if($user->can($name) && !$value) {
                $permissionsChanged['attributes'][$name] = 'false';
                $permissionsChanged['old'][$name] = 'true';
                $user->revokePermissionTo($name);
            } elseif($user->cannot($name) && $value) {
                $permissionsChanged['attributes'][$name] = 'true';
                $permissionsChanged['old'] = 'false';
                $user->givePermissionTo($name);
            }
        }

        if($permissionsChanged != [])
            activity()->performedOn($user)
                ->withProperties($permissionsChanged)
                ->log('permissionChange');
    }

    public function GetByUserId($userId) {
        $user = User::where('user_id', $userId)->first();

        return $user->getAllPermissions();
    }
}
