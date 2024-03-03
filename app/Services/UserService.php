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
        $user = User::create($userData);

        $makeMeAPassword = 'https://makemeapassword.ligos.net/api/v1/passphrase/json?wc=4&whenUp=StartOfWord&ups=2&minCh=20';
        $curl = curl_init($makeMeAPassword);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        $response = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($response, true);
        $password = empty($data['pws']) ? \Hash::make(Str::random(15)) : $password = \Hash::make($data['pws'][0]);
        $user->update(['password', $password]);

        return $user;
    }

    public function delete($userId) {
    }

    // public function update($user, $contactId, $primaryEmailAddress = null, $userId) {

    // }
}

?>
