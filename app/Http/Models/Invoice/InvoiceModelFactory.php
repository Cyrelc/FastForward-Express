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

		public function GetLayoutModel($req, $id) {
			$model = new Invoice\InvoiceLayoutModel();

			$acctRepo = new Repos\AccountRepo();

			$model->account = $acctRepo->GetById($id);
			$parent_id = $model->account->parent_account_id;

			while(isset($parent_id)) {
				$parent_name = $acctRepo->GetById($parent_id)->name;
				array_push($model->parents, $parent_name);
				$parent_id = $acctRepo->GetById($parent_id)->parent_account_id;
			}

			return $model;
		}
	}
