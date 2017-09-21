<?php

	namespace App\Http\Models\Invoice;

	use App\Http\Repos;
	use App\Http\Models\Invoice;
	use App\Http\Models\Bill;

	class InvoiceModelFactory{

		public function ListAll() {
			$model = new Invoice\InvoicesModel();

			$invoiceRepo = new Repos\InvoiceRepo();
			$accountRepo = new Repos\AccountRepo();
			$billRepo = new Repos\BillRepo();
			
			$invoices = $invoiceRepo->ListAll();

			$invoice_view_models = array();
			foreach ($invoices as $invoice) {
				$invoice_view_model = new InvoiceViewModel();

				$invoice_view_model->invoice = $invoice;
				$invoice_view_model->account = $accountRepo->GetById($invoice->account_id);
				$invoice_view_model->bill_count = $billRepo->CountByInvoiceId($invoice->invoice_id);

				array_push($invoice_view_models, $invoice_view_model);
			}

			$model->invoices = $invoice_view_models;
			$model->success = true;

			return $model;
		}

		public function GetById($id) {
			$model = new InvoiceViewModel();

			$invoiceRepo = new Repos\InvoiceRepo();
			$accountRepo = new Repos\AccountRepo();
			$addressRepo = new Repos\AddressRepo();
			$billRepo = new Repos\BillRepo();

			$model->invoice = $invoiceRepo->GetById($id);

			$parent_id = $model->invoice->account_id;
			while (!is_null($parent_id)) {
				$parent = $accountRepo->GetById($parent_id);
				array_push($model->parents, $parent);
				$parent_id = $parent->parent_id;
			}

			$bills = $billRepo->GetByInvoiceId($id);
			foreach ($bills as $bill) {
				$bill_model = new Bill\BillViewModel();
				$bill_model->bill = $bill;
				$bill_model->pickup_address = $addressRepo->GetById($bill->pickup_address_id);
				$bill_model->delivery_address = $addressRepo->GetById($bill->delivery_address_id);
				array_push($model->bills, $bill_model);
			}

			$model->amount = $billRepo->GetInvoiceCost($id);
			$model->tax = number_format(round($model->amount * .05, 2), 2, '.', '');
			$model->total = $model->amount + $model->tax;

			return $model;
		}

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
