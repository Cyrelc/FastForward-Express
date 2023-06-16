<?php

namespace App\Http\Models\Invoice;

use App\Http\Repos;
use App\Http\Models\Invoice;
use App\Http\Models\Bill;

class InvoiceModelFactory{
	public function GetById($req, $invoiceId) {
		$model = new InvoiceViewModel();

		$permissionModelFactory = new \App\Http\Models\Permission\PermissionModelFactory();
		$accountRepo = new Repos\AccountRepo();
		$addressRepo = new Repos\AddressRepo();
		$billRepo = new Repos\BillRepo();
		$invoiceRepo = new Repos\InvoiceRepo();
		$lineItemRepo = new Repos\LineItemRepo();

		$model->invoice = $invoiceRepo->GetById($invoiceId);
		$model->invoice->bill_count = $billRepo->CountByInvoiceId($model->invoice->invoice_id);
		if($model->invoice->account_id)
			$model->invoice->bill_count_with_missed_line_items = $lineItemRepo->CountUninvoicedByInvoiceSettings($model->invoice);

		$amendments = $billRepo->GetAmendmentsByInvoiceId($model->invoice->invoice_id);
		if(count($amendments)) {
			$billEndDate = new \DateTime($model->invoice->bill_end_date);
			$currentDate = new \DateTime('now');
			$diff = $currentDate->diff($billEndDate, true);
			if($diff->days < (int)config('ffe_config.days_invoice_editable'))
				$model->can_edit_amendments = true;
			else
				$model->can_edit_amendments = false;

			foreach($amendments as $amendment)
				$amendment->line_items = $lineItemRepo->GetAmendmentsByBillAndInvoiceId($amendment->bill_id, $model->invoice->invoice_id);

			$model->amendments = $amendments;
		}

		if(isset($model->invoice->account_id)) {
			$model->parent = $accountRepo->GetById($model->invoice->account_id);

			$model->parent->shipping_address = $addressRepo->GetById($model->parent->shipping_address_id);
			if(isset($model->parent->billing_address_id) && $model->parent->billing_address_id != '')
				$model->parent->billing_address = $addressRepo->GetById($model->parent->billing_address_id);
			else
				$model->parent->billing_address = $model->parent->shipping_address;

			$model->tables = $billRepo->GetForAccountInvoice($model->invoice->invoice_id);
			$subtotalBy = $accountRepo->GetSubtotalByField($model->parent->account_id);
			if($subtotalBy != false) {
				foreach($model->tables as $billSubTable) {
					$subtotalDatabaseFieldName = $subtotalBy->database_field_name;
					$billSubTable->subtotal = $billRepo->GetInvoiceSubtotalByField($model->invoice->invoice_id, $subtotalDatabaseFieldName, $billSubTable->bills[0]->$subtotalDatabaseFieldName);
					if ($model->parent->gst_exempt)
						$billSubTable->tax = number_format(0, 2, '.', '');
					else
						$billSubTable->tax = number_format(round($billSubTable->subtotal * (float)config('ffe_config.gst') / 100, 2), 2, '.', '');

					$billSubTable->total = $billSubTable->subtotal + $billSubTable->tax;
				}
			}
			foreach($model->tables as $index => $table) {
				$table->headers = array('Date' => 'time_pickup_scheduled', 'Bill ID' => 'bill_id', 'Waybill Number' => 'bill_number');
				foreach($table->bills as $key => $bill)
					$model->tables[$index]->bills[$key]->line_items = $lineItemRepo->GetByBillAndInvoiceId($bill->bill_id, $model->invoice->invoice_id);
				if($subtotalBy != NULL && $subtotalBy->database_field_name == 'charge_account_id') {
					$customField = $accountRepo->GetById($table->bills[0]->charge_account_id)->custom_field;
					if($customField)
						$table->headers[$customField] = 'charge_reference_value';
				}
				else if($model->parent->custom_field)
					$table->headers[$model->parent->custom_field] = 'charge_reference_value';
				$table->headers['Pickup Address'] = 'pickup_address_name';
				$table->headers['Delivery Address'] = 'delivery_address_name';
				// $table->headers['Type'] = 'delivery_type';
				$table->headers['Price'] = 'amount';
			}

			$model->unpaid_invoices = $invoiceRepo->GetOutstandingByAccountId($model->invoice->account_id);

			$model->account_owing = $invoiceRepo->CalculateAccountBalanceOwing($model->invoice->account_id);
		} else {
			$paymentRepo = new Repos\PaymentRepo();

			$model->parent = new \stdClass;
			$model->parent->name = $paymentRepo->GetPaymentType($model->invoice->payment_type_id)->name;
			$model->tables = array();
			$model->tables[0] = new \stdClass;
			$model->tables[0]->headers = [
				'Date' => 'time_pickup_scheduled',
				'Bill ID' => 'bill_id',
				'Waybill Number' => 'bill_number',
				'Pickup Address' => 'pickup_address_name',
				'Delivery Address' => 'delivery_address_name',
				'Price' => 'amount'
			];

			$model->tables[0]->bills = $billRepo->GetForPrepaidInvoice($model->invoice->invoice_id);
			foreach($model->tables[0]->bills as $key => $bill)
				$model->tables[0]->bills[$key]->line_items = $lineItemRepo->GetByBillAndInvoiceId($bill->bill_id, $model->invoice->invoice_id);
			$model->parent->account_number = 'Bill# ' . $model->tables[0]->bills[0]->bill_id;
		}

		$model->permissions = $permissionModelFactory->GetInvoicePermissions($req->user(), $model->invoice);

		return $model;
	}

	public function GetCreateModel() {
		$selectionsRepo = new Repos\SelectionsRepo();

		$model = new Invoice\InvoiceFormModel();

		$model->invoice_intervals = $selectionsRepo->GetSelectionsListByType('invoice_interval');
		$model->start_date = date('Y-m-d H:i:s', strtotime("first day of last month"));
		$model->end_date = date('Y-m-d H:i:s', strtotime("last day of last month"));

		return $model;
	}

	public function GetGenerateModel($req) {
        $startDate = (new \DateTime($req->start_date))->format('Y-m-d');
        $endDate = (new \DateTime($req->end_date))->format('Y-m-d');

		$accountRepo = new Repos\AccountRepo();
		$model = new GenerateInvoiceViewModel();

		$model->accounts = $accountRepo->GetWithUninvoicedLineItems($req->invoice_intervals, $startDate, $endDate);

		return $model;
	}
}

?>
