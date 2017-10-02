<?php
namespace App\Http\Repos;

use App\User;
use Illuminate\Support\Facades\Hash;

class UserRepo
{
    public function GetById($id) {
        $user = User::where('user_id', '=', $id)->first();

        return $user;
    }

    public function Insert($user, $roleName) {
        $new = new User;
//TODO: UNDO
        // $new->user_id = 1;
        // return $new;

        $pass = '';
        $user = array_merge($user, array(
            'password' => Hash::make($pass),
            'is_locked' => false,
            'login_attempts' => 0,
            'remember_token' => null
        ));

        $new = $new->create($user);

        $roleRepo = new RolesRepo();
        $userRoleRepo = new UserRoleRepo();
        $role = $roleRepo->GetByName($roleName);
        $userRoleRepo->AddUserToRole($new, $role);

        return $new;
    }

    public function Update($user, $roleNames) {
        $old = $this->GetById($user['user_id']);

        $old->username = $user['username'];
        $old->email = $user['email'];

        $old->save();

        $userRoleRepo = new UserRoleRepo();
        $roleRepo = new RolesRepo();
        $roles = [];

        foreach($roleNames as $roleName)
            array_push($roles, $roleRepo->GetByName($roleName));

        $userRoleRepo->UpdateRoles($old, $roles);

        return $old;
    }
}
