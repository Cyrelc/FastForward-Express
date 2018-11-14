<?php

namespace App\Http\Repos;

use App\Package;

class PackageRepo {
	public function GetById($package_id){
		$package = Package::where('package_id', '=', $package_id)->first();

		return $package;
	}

	public function GetByBillId($bill_id) {
		$packages = Package::where('bill_id', '=', $bill_id)->get();

		return $packages;
	}

    public function Insert($package) {
    	$new = new Package;

    	return $new->create($package);
    }

    public function Update($package) {
    	$old = $this->GetById($package['package_id']);

		$old->count = $package['count'];
    	$old->weight = $package['weight'];
    	$old->height = $package['height'];
    	$old->length = $package['length'];
    	$old->width = $package['width'];

    	$old->save();

    	return $old;
    }

    public function Delete($package_id) {
    	$old = $this->GetById($package_id);

    	$old->delete();
    	return;
	}
	
	public function DeleteByBillId($bill_id) {
		$packages = $this->GetByBillId($bill_id);
		foreach($packages as $package)
			$this->Delete($package->package_id);
	}
}

?>
