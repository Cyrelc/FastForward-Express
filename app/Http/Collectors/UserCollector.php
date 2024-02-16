<?php

namespace App\Http\Collectors;

use App\Http\Repos;

class UserCollector {
    public function collect($req, $userId = null) {
        foreach($req->email_addresses as $email)
        if(filter_var($email['is_primary'], FILTER_VALIDATE_BOOLEAN)) {
            $primaryEmail = $email['email'];
            break;
        }
        if(!$primaryEmail)


        if(isset($req->employee_id) && $req->employee_id !== '') {
            $employeeRepo = new Repos\EmployeeRepo();
            $userId = $employeeRepo->getById($req->employee_id, null)->user_id;
        }

        $user = [
            // 'username' => substr($req->first_name, 0, 1) . $req->last_name,
            'is_enabled' => filter_var($req->is_enabled, FILTER_VALIDATE_BOOLEAN),
            'email' => $primaryEmail,
            'user_id' => $userId
        ];

        return $user;
    }

    public function collectSettings($req) {
        return [
            'use_imperial_default' => filter_var($req->use_imperial_default, FILTER_VALIDATE_BOOLEAN)
        ];
    }
}
