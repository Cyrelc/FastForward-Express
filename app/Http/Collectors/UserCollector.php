<?php
/**
 * Created by PhpStorm.
 * User: jacks
 * Date: 6/28/2017
 * Time: 10:07 AM
 */

namespace App\Http\Collectors;


class UserCollector {
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
