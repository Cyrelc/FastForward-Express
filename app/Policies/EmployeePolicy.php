<?php

namespace App\Policies;

use App\Models\Employee;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Employee  $employee
     * @return mixed
     */
    public function viewBasic(User $user, Employee $employee) {
        return $user->hasAnyPermission('employees.view.*.*', 'employees.edit.*.*', 'employees.view.basic.*') ||
            $user->employee && $user->employee->employee_id === $employee->employee_id;
    }

    public function viewAdvanced(User $user, Employee $employee) {
        return $user->hasAnyPermission('employees.view.*.*', 'employees.edit.*.*');
    }

    public function viewAny(User $user) {
        return $user->hasAnyPermission('employees.view.*.*', 'employees.edit.*.*', 'employees.view.basic.*', 'employees.edit.basic.*') ||
            ($user->employee && $user->is_enabled);
    }

    public function viewAll(User $user) {
        return $user->hasAnyPermission('employees.view.basic.*', 'employees.view.*.*', 'employees.edit.*.*', 'employees.edit.basic.*');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('employees.create');
    }

    /**
     * Determine whether the user can update basic sections of the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Employee  $employee
     * @return mixed
    */
    public function updateBasic(User $user, Employee $employee) {
        return $user->hasAnyPermission('employees.edit.basic.*', 'employees.edit.*.*') ||
            ($user->employee && $user->employee->active && $user->employee->employee_id === $employee->employee_id);
    }

    public function updateAdvanced(User $user, Employee $employee) {
        return $user->can('employees.edit.*.*');
    }

    public function updatePermissions(User $user, Employee $employee) {
        return $user->can('employees.edit.*.*');
    }

    public function viewActivityLog(User $user, Employee $employee) {
        return $user->can('employees.edit.*.*');
    }
}
