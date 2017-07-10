<?php
/**
 * Created by PhpStorm.
 * User: jacks
 * Date: 6/28/2017
 * Time: 10:07 AM
 */

namespace App\Http\Collectors;


class UserCollector {
    public function CollectDriver($req, $prefix) {
        $user = [
            'username' => substr($req->input($prefix . '-first-name'), 0, 1) . $req->input($prefix . '-last-name'),
            'email' => $req->input($prefix . '-email1'),
        ];

        return $user;
    }
}