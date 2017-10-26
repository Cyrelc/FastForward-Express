<?php

namespace App\Http\Models\Interliner;

use App\Http\Repos;
use App\Http\Models\Interliner;

class InterlinerModelFactory{

	public function GetCreateModel($req) {
		$model = new InterlinerFormModel();
		$model->interliner = new \App\Interliner();
		$model->interliner->address = new \App\Address();

		return $model;
	}
}

?>
