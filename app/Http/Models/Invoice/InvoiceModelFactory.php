<?php

	namespace App\Http\Models\Invoice;

	use App\Http\Repos;
	use App\Http\Models\Invoice;
	use App\Http\Models\Bill;

	class InvoiceModelFactory{

		public function ListAll() {
			$invoiceRepo = new Repos\InvoiceRepo();

			return $invoiceRepo->ListAll();
		}

		public function GetById($id) {
			$model = new InvoiceViewModel();

			$invoiceRepo = new Repos\InvoiceRepo();
			$accountRepo = new Repos\AccountRepo();
			$addressRepo = new Repos\AddressRepo();
			$billRepo = new Repos\BillRepo();

			$model->invoice = $invoiceRepo->GetById($id);
			$model->invoice->bill_count = $billRepo->CountByInvoiceId($id);
			$invoice_numbers = array('bill_cost', 'tax', 'discount', 'total_cost', 'fuel_surcharge', 'balance_owing');
			foreach ($invoice_numbers as $identifier) {
				$model->invoice->$identifier = number_format($model->invoice->$identifier, 2);
			}

			$model->parent = $accountRepo->GetById($model->invoice->account_id);

			$model->parent->shipping_address = $addressRepo->GetById($model->parent->shipping_address_id);
			if(isset($model->parent->billing_address_id) && $model->parent->billing_address_id != '')
				$model->parent->billing_address = $addressRepo->GetById($model->parent->billing_address_id);
			else
				$model->parent->billing_address = $model->parent->shipping_address;

			$model->tables = $billRepo->GetByInvoiceId($id);
			$subtotal_by = $invoiceRepo->GetSubtotalById($model->parent->account_id);
			if(count($model->tables) > 1) {
				foreach($model->tables as $bill_sub_table) {
					$subtotal_database_field_name = $subtotal_by->database_field_name;
					$bill_sub_table->subtotal = $billRepo->GetInvoiceSubtotalByField($id, $subtotal_database_field_name, $bill_sub_table->bills[0]->$subtotal_database_field_name);
					$bill_sub_table->tax = $bill_sub_table->subtotal * 0.05;
					$bill_sub_table->total = number_format($bill_sub_table->subtotal + $bill_sub_table->tax, 2);
					$bill_sub_table->subtotal = number_format($bill_sub_table->subtotal, 2);
					$bill_sub_table->tax = number_format($bill_sub_table->tax, 2);
				}
			}
			foreach($model->tables as $table) {
				$table->headers = array('Date' => 'pickup_date_scheduled', 'Waybill Number' => 'bill_number');
				if($subtotal_by != NULL && $subtotal_by->database_field_name == 'charge_account_id')
					$table->headers[$accountRepo->GetById($table->bills[0]->charge_account_id)->custom_field] = 'charge_reference_value';
				else if($model->parent->uses_custom_field)
					$table->headers[$model->parent->custom_field] = 'charge_reference_value';
				$table->headers['Tracer #'] = 'bill_id';
				$table->headers['Address'] = 'address';
				$table->headers['Type'] = 'delivery_type';
				$table->headers['Amount'] = 'amount';
			}

			$model->unpaid_invoices = $invoiceRepo->GetOutstandingByAccountId($model->invoice->account_id);

			$model->account_owing = number_format($invoiceRepo->CalculateAccountBalanceOwing($model->invoice->account_id), 2);

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
