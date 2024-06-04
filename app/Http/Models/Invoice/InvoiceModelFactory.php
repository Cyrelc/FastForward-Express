<?php

namespace App\Http\Models\Invoice;

use App\Http\Repos;
use App\Models\Invoice;
use App\Models\Payment;
use App\Http\Models\Payment\PaymentModelFactory;
use App\Http\Models\Permission\PermissionModelFactory;
use App\Http\Resources\PaymentResource;

class InvoiceModelFactory{
	public function GetById($req, $invoiceId) {
		$model = new InvoiceViewModel();

		$permissionModelFactory = new PermissionModelFactory();

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

		//If the invoice belongs to an account
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

			if($req->user()->can('viewPayments', $model->parent))
				$model->payments = PaymentResource::collection(Payment::where('invoice_id', $invoiceId)->get());

			if($req->user()->can('processPayments', $model->parent)) {
				$paymentModelFactory = new PaymentModelFactory();
				$model->cards_on_file = $paymentModelFactory->GetAccountStripePaymentMethods($model->parent);
			}
		// Otherwise the model belongs to a prepaid, one off invoice
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
			$masterBill = $model->tables[0]->bills[0];
			foreach($model->tables[0]->bills as $key => $bill)
				$model->tables[0]->bills[$key]->line_items = $lineItemRepo->GetByBillAndInvoiceId($bill->bill_id, $model->invoice->invoice_id);
			$model->parent->account_number = 'Bill# ' . $masterBill->bill_id;

			$model->parent->billing_address = $addressRepo->GetById($masterBill->pickup_address_id);
			$model->parent->shipping_address = $addressRepo->GetById($masterBill->delivery_address_id);
			$model->parent->invoice_comment = $masterBill->description;
			$model->unpaid_invoices = array();
			$model->is_prepaid = true;
			$model->account_owing = $model->invoice->balance_owing;

			if($req->user()->can('payments.view.*.*'))
				$model->payments = PaymentResource::collection(Payment::where('invoice_id', $invoiceId)->get());
		}

		$model->permissions = $permissionModelFactory->GetInvoicePermissions($req->user(), $model->invoice);

		return $model;
	}

	public function GetGenerateModel($req) {
        $startDate = (new \DateTime($req->start_date))->format('Y-m-d');
        $endDate = (new \DateTime($req->end_date))->format('Y-m-d');

		$accountRepo = new Repos\AccountRepo();
		$chargeRepo = new Repos\ChargeRepo();
		$model = new GenerateInvoiceViewModel();

		$model->pending_creation = array();
		if($req->invoice_intervals)
			$model->pending_creation = $accountRepo->GetWithUninvoicedLineItems($req->invoice_intervals, $startDate, $endDate)->toArray();
		if($req->prepaid_types)
			$model->pending_creation = array_merge($model->pending_creation, $chargeRepo->GetWithUninvoicedPrepaid($req->prepaid_types, $startDate, $endDate)->toArray());

		return $model;
	}
}

?>
