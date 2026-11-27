<?php

namespace App\Services;

use App\Models\User;
use App\Http\Repos\UserRepo;
use App\Http\Collectors\UserCollector;
use Illuminate\Support\Str;

class UserService {
    public function __construct() {
    }

    public function create($userData) {
        // A placeholder no one knows: new users set their own through a password reset. (This used to fetch one from
        // a third-party site, then failed to save it - update(['password', ...]) is a list, not a key - leaving the
        // password blank, which no login accepts either.) password is guarded, so it is set directly.
        $user = new User($userData);
        $user->password = \Hash::make(Str::random(40));
        $user->save();

        return $user;
    }

    public function delete($userId) {
    }

    // public function update($user, $contactId, $primaryEmailAddress = null, $userId) {

    // }
}

?>
