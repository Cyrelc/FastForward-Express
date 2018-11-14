<?php

namespace App\Http\Collectors;

class PackageCollector {
	public function Collect($req, $bill_id) {
        $package_names = [];
        foreach($req->all() as $key => $value) {
            if(substr_compare($key, 'package', 0, 7) == 0 && !in_array(explode('_', $key, 2)[0], $package_names))
                array_push($package_names, explode('_', $key, 2)[0]);
        }
        $packages = [];
        foreach($package_names as $name) {
            $new_package = [
                'package_id' => $req->input($name . '_id') == null ? '' : $req->input($name . '_id'),
                'bill_id' => $bill_id,
                'count' => $req->input($name . '_count'),
                'weight' => $req->input($name . '_weight'),
                'height' => $req->input($name . '_height'),
                'width' => $req->input($name . '_width'),
                'length' => $req->input($name . '_length')
            ];
            array_push($packages, $new_package);
        }
        return $packages;
	}
}
