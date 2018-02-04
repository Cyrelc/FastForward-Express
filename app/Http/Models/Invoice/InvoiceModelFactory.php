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

			$model->parent = $accountRepo->GetById($model->invoice->account_id);

			$model->parent->shipping_address = $addressRepo->GetById($model->parent->shipping_address_id);
			if(isset($model->parent->billing_address_id) && $model->parent->billing_address_id != '')
				$model->parent->billing_address = $addressRepo->GetById($model->parent->billing_address_id);
			else
				$model->parent->billing_address = $model->parent->shipping_address;

			$sort_options = $invoiceRepo->GetSortOrderById($model->invoice->account_id);
			$model->headers = array('Date' => 'date', 'Bill Number' => 'bill_number', 'Pickup Location' => 'pickup_address_name', 'Delivery Location' => 'delivery_address_name', 'Amount' => 'amount');
			$bills = $billRepo->GetByInvoiceId($id, $sort_options);

			foreach($bills as $account_id => $bill) {
				$table = $model->tables[$account_id] = new InvoiceTable();
				$model->tables[$account_id]->charge_account_name = $bill[0]->charge_account_name;
				$model->tables[$account_id]->charge_account_id = $bill[0]->charge_account_id;
				foreach($bill as $current) {
					$line = new InvoiceLine();
					$table->bill_subtotal += $current->amount + $current->interliner_amount;
					$line->amount = number_format($current->amount + $current->interliner_amount, 2);
					$line->is_subtotal = false;
					$list = array('date', 'bill_number', 'pickup_address_name', 'delivery_address_name');
					foreach($list as $item)
						$line->$item = $current->$item;
					array_push($table->lines, $line);
				}
				$table->bill_subtotal = number_format($table->bill_subtotal, 2);
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
