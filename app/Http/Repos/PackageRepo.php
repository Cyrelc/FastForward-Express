<?php

namespace App\Http\Repos;

use App\Package;

class PackageRepo {
    public function Insert($package) {
    	$new = new Package;

    	return ($new->create($package));
    }
}

?>
