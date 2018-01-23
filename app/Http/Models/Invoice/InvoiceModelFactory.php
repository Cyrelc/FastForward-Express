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
			$invoice_numbers = array('bill_cost', 'tax', 'discount', 'total_cost', 'fuel_surcharge', 'balance_owing');
			foreach ($invoice_numbers as $identifier){
				$model->invoice->$identifier = number_format($model->invoice->$identifier, 2);
			}

			$parent_id = $model->invoice->account_id;
			while (!is_null($parent_id)) {
				$parent = $accountRepo->GetById($parent_id);
				array_push($model->parents, $parent);
				$parent_id = $parent->parent_account_id;
			}

			$sort_options = $invoiceRepo->GetSortOrderById($model->invoice->account_id);
			$model->headers = array('Date' => 'date', 'Bill Number' => 'bill_number', 'Delivery' => 'delivery_address_name', 'Pickup' => 'pickup_address_name', 'Amount' => 'amount');
			$bills = $billRepo->GetByInvoiceId($id, $sort_options);

			$subtotals = array();
			foreach($sort_options as $option)
				if($option->subtotal) {
					array_push($subtotals, array('field' => $option->database_field_name, 'current' => '', 'tally' => 0));
				}

			foreach($bills as $bill) {
				$line = new InvoiceLine();
				foreach($subtotals as $key => $value)
					if($bill[$value['field']] === $value['current'])
						$subtotals[$key]['tally'] += $bill->amount + $bill->interliner_amount;
					else {
						if($value['tally'] != 0) {
							$line->amount = number_format($value['tally'], 2);
							$line->is_subtotal = true;
							$temp = $value['field'];
							$line->$temp = $value['current'];
							array_push($model->table, $line);
							$line = new InvoiceLine();
						}
						$subtotals[$key]['current'] = $bill[$value['field']];
						$subtotals[$key]['tally'] = $bill->amount + $bill->interliner_amount;
					}
				foreach($model->headers as $key => $value)
					if($key == 'Amount')
						$line->amount = number_format($bill->amount + $bill->interliner_amount, 2);
					else
						$line->$value = $bill[$value];
				array_push($model->table, $line);
			}
			
			foreach($subtotals as $key => $value) {
				$line = new InvoiceLine();
				$line->amount = $value['tally'];
				array_push($model->table, $line);
			}

			return $model;
		}

		public function GetCreateModel($req) {
			$selectionsRepo = new Repos\SelectionsRepo();

			$model = new Invoice\InvoiceFormModel();

			$model->invoice_intervals = $selectionsRepo->GetSelectionsByType('invoice_interval');
			$model->start_date = date("U");
			$model->end_date = date("U");

			return $model;
		}

		public function GetLayoutModel($req, $id) {
			$model = new Invoice\InvoiceLayoutModel();

			$acctRepo = new Repos\AccountRepo();
			$invoiceRepo = new Repos\InvoiceRepo();

			$model->account = $acctRepo->GetById($id);
			$parent_id = $model->account->parent_account_id;

			while(isset($parent_id)) {
				$parent_name = $acctRepo->GetById($parent_id)->name;
				array_push($model->parents, $parent_name);
				$parent_id = $acctRepo->GetById($parent_id)->parent_account_id;
			}

			$model->sort_options = $invoiceRepo->GetSortOrderById($id);

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
