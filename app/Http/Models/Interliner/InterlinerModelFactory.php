<?php

namespace App\Http\Models\Interliner;

use App\Http\Repos;
use App\Models\Address;
use App\Models\Interliner;

class InterlinerModelFactory{

	public function ListAll() {
		$model = new InterlinersModel();
		$interlinerRepo = new Repos\InterlinerRepo();
		$addressRepo = new Repos\AddressRepo();

		$interliners = $interlinerRepo->ListAll();
		foreach ($interliners as $interliner) {
			$interliner_view_model = new InterlinerFormModel();

			$interliner_view_model->interliner = $interliner;
			$interliner_view_model->address = $addressRepo->GetById($interliner->address_id);

			array_push($model->interliners, $interliner_view_model);
		}

		return $model;
	}

	public function GetCreateModel($req) {
		$model = new InterlinerFormModel();
		$model->interliner = new Interliner();
		$model->interliner->address = new Address();

		return $model;
	}

	public function GetEditModel($req, $id) {
		$model = new InterlinerFormModel();
		$interlinerRepo = new Repos\InterlinerRepo();
		$addressRepo = new Repos\AddressRepo();

		$model->interliner = $interlinerRepo->GetById($id);
		$model->interliner->address = $addressRepo->GetById($model->interliner->address_id);

		return $model;
	}
}

?>
