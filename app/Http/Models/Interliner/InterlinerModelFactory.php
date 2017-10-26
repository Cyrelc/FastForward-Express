<?php

namespace App\Http\Models\Interliner;

use App\Http\Repos;
use App\Http\Models\Interliner;

class InterlinerModelFactory{

	public function ListAll() {
		$model = new InterlinersModel();
		$interlinerRepo = new Repos\InterlinerRepo();
		$addressRepo = new Repos\AddressRepo();

		$interliners = $interlinerRepo->ListAll();
		foreach ($interliners as $interliner) {
			$interliner_view_model = new InterlinerFormModel();
			$interliner_view_model->interliner = $interliner;
			$address = $addressRepo->GetById($interliner->address_id);
			$interliner_view_model->address = $address->street . ' ' . $address->city . ' ' . $address->state_province . ' ' . $address->country . ' ' . $address->zip_postal;

//			$interliner_view_model->address = $address;
			array_push($model->interliners, $interliner_view_model);
		}

		return $model;
	}

	public function GetCreateModel($req) {
		$model = new InterlinerFormModel();
		$model->interliner = new \App\Interliner();
		$model->interliner->address = new \App\Address();

		return $model;
	}
}

?>
