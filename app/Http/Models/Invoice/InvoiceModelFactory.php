<?php

	namespace App\Http\Models\Invoice;

	use App\Http\Repos;
	use App\Http\Models\Invoice;

	class InvoiceModelFactory{

		public function GetCreateModel($req) {
			$model = new Invoice\InvoiceFormModel();

			$model->invoice_intervals = array('weekly', 'twice monthly', 'monthly');
			$model->start_date = date("U");
			$model->end_date = date("U");

			return $model;
		}
	}
