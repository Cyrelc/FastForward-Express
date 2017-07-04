<?php
namespace App\Http\Repos;

use App\Interliner;

class InterlinerRepo {

    public function ListAll() {
        $interliners = Interliner::All();

        return $interliners;
    }
}

?>
