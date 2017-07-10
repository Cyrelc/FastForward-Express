<?php
namespace App\Http\Repos;

use \App\UserRole;

class UserRoleRepo {
    public function ListRolesByUser($userId) {
        $userRoles = UserRole::where('user_id', '=', $userId)->get();

        $roles = [];
        $roleRepo = new RolesRepo();
        foreach($userRoles as $ur)
            array_push($roles, $roleRepo->GetById($ur->role_id));

        return $roles;
    }

    public function AddUserToRole($user, $role) {
        \DB::table('user_roles')->insert(array(
            'user_id' => $user->user_id,
            'role_id' => $role->role_id
        ));
    }

    public function RemoveUserFromRole($user, $role) {
        \DB::table('user_roles')->delete(array(
            'user_id' => $user->user_id,
            'role_id' => $role->role_id
        ));
    }

    public function UpdateRoles($user, $roles) {
        $oldRoles = $this->ListRolesByUser($user->user_id);

        foreach($oldRoles as $oldRole)
            if (!in_array($oldRole, $roles))
                $this->RemoveUserFromRole($user, $oldRole);

        foreach($roles as $role)
            if (!in_array($role, $oldRoles))
                $this->AddUserToRole($user, $role);
    }
}
