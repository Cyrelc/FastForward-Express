<?php

	namespace App\Http\Models\Invoice;

	use App\Http\Repos;
	use App\Http\Models\Invoice;
	use App\Http\Models\Bill;

	class InvoiceModelFactory{

		public function GetById($id) {
			$model = new InvoiceViewModel();

			$accountRepo = new Repos\AccountRepo();
			$addressRepo = new Repos\AddressRepo();
			$billRepo = new Repos\BillRepo();
			$invoiceRepo = new Repos\InvoiceRepo();

			$model->invoice = $invoiceRepo->GetById($id);
			$model->invoice->bill_count = $billRepo->CountByInvoiceId($id);
			$invoice_numbers = array('bill_cost', 'tax', 'discount', 'total_cost', 'fuel_surcharge', 'balance_owing', 'min_invoice_amount');
			foreach ($invoice_numbers as $identifier) {
				if($model->invoice->$identifier == null)
					continue;
				$model->invoice->$identifier = number_format($model->invoice->$identifier, 2);
			}

			$amendments = $invoiceRepo->GetAmendmentsByInvoiceId($id);
			if(count($amendments)) {
				$billEndDate = new \DateTime($model->invoice->bill_end_date);
				$currentDate = new \DateTime('now');
				$diff = $currentDate->diff($billEndDate, true);
				if($diff->days < (int)config('ffe_config.days_invoice_editable'))
					$model->can_edit_amendments = true;
				else
					$model->can_edit_amendments = false;
				$model->amendments = $amendments;
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
				$table->headers = array('Date' => 'time_pickup_scheduled', 'Bill ID' => 'bill_id', 'Waybill Number' => 'bill_number');
				if($subtotal_by != NULL && $subtotal_by->database_field_name == 'charge_account_id') {
					$customField = $accountRepo->GetById($table->bills[0]->charge_account_id)->custom_field;
					if($customField)
						$table->headers[$customField] = 'charge_reference_value';
				}
				else if($model->parent->uses_custom_field)
					$table->headers[$model->parent->custom_field] = 'charge_reference_value';
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
			$model->start_date = strtotime("first day of last month");
			$model->end_date = strtotime("last day of last month");

			return $model;
		}

		public function GetGenerateModel($invoice_interval, $start_date, $end_date) {
			$start_date = new \DateTime($start_date);
			$end_date = new \DateTime($end_date);

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
