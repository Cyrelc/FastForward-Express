<?php
namespace App\Http\Repos;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionRepo {
    public function assignUserPermissions($user, $permissions) {
        $permissionsChanged = [];

        foreach($permissions as $name => $value) {
            if($user->can($name) && $value == false) {
                $permissionsChanged['attributes'][$name] = false;
                $permissionsChanged['old'][$name] = true;
                $user->revokePermissionTo($name);
            } elseif($user->cannot($name) && $value == true) {
                $permissionsChanged['attributes'][$name] = true;
                $permissionsChanged['old'][$name] = false;
                $user->givePermissionTo($name);
            }
        }

        if($permissionsChanged != [])
            activity()->performedOn($user)
                ->withProperties($permissionsChanged)
                ->log('permissionChange');
    }

    public function GetByUserId($userId) {
        $user = User::find($userId);

        return $user->getAllPermissions();
    }
}
