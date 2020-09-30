<?php

namespace App\Http\Collectors;

use App\Http\Repos;

class UserCollector {
    public function CollectUserForEmployee($req) {
        $userId = null;
        foreach($req->emails as $email)
        if(filter_var($email['is_primary'], FILTER_VALIDATE_BOOLEAN)) {
            $primaryEmail = $email['email'];
            break;
        }

        if(isset($req->employee_id) && $req->employee_id !== '') {
            $employeeRepo = new Repos\EmployeeRepo();
            $userId = $employeeRepo->GetById($req->employee_id)->user_id;
        }

        $user = [
            'username' => substr($req->first_name, 0, 1) . $req->last_name,
            'email' => $primaryEmail,
            'user_id' => $userId
        ];

        return $user;
    }

    public function CollectEmployee($req, $prefix) {
        $user = [
            'username' => substr($req->input($prefix . '-first-name'), 0, 1) . $req->input($prefix . '-last-name'),
            'email' => $req->input($prefix . '-email1'),
        ];

        return $user;
    }

    public function Collect($req) {
        $user = [
            'email' => $req->email[$req->email_is_primary[0]],
            'is_locked' => !$req->enabled
        ];

        return $user;
    }

    public function CollectAccountUser($account_id, $contact_id, $is_primary, $user_id = null) {
        $account_user = [
            'account_id' => $account_id,
            'contact_id' => $contact_id,
            'is_primary' => $is_primary,
            'user_id' => $user_id
        ];

        return $account_user;
    }
}
