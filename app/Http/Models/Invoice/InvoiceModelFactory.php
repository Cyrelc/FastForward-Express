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

		public function GetGenerateModel($invoice_interval, $start_date, $end_date) {
			$start_date = (new \DateTime($start_date))->format('Y-m-d');
			$end_date = (new \DateTime($end_date))->format('Y-m-d');

			$repo = new Repos\AccountRepo();
			$model = new GenerateInvoiceViewModel();

			try {
				$model->accounts = $repo->ListAllWithUninvoicedBillsByInvoiceInterval($invoice_interval, $start_date, $end_date);
			} catch (Exception $e) {
				$model->friendlyMessage = 'Sorry, but an error has occurred. Please contact support.';
			    $model->errorMessage = $e;
			}

			return $model;
		}

	}
