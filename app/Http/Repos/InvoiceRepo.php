<?php
namespace App\Http\Repos;

use App\Bill;
use App\Invoice;

class InvoiceRepo {
    public function ListAll() {
        $invoices = Invoice::All();

        return $invoices;
    }

    public function GetById($id) {
        $invoice = Invoice::where('invoice_id', '=', $id)->first();

        return $invoice;
    }

    public function Create($account_ids, $start_date, $end_date) {
        $invoice_ids = [];

        if (is_array($account_ids))
            foreach($account_ids as $account_id)
                array_push($invoice_ids, $this->GenerateInvoice($account_id, $start_date, $end_date));
        else
            array_push($invoice_ids, $this->GenerateInvoice($account_ids, $start_date, $end_date));

        return $invoice_ids;
    }

    public function Delete($invoiceId) {
        $bills = Bill::where('invoice_id', '=', $invoiceId)->get();
        $invoice = Invoice::where('invoice_id', '=', $invoiceId)->first();

        foreach($bills as $bill) {
            $bill->invoice_id = null;
            $bill->is_invoiced = false;

            $bill->save();
        }

        $invoice->delete();
        return;
    }

    public function GenerateInvoice($account_id, $start_date, $end_date) {
        $bills = Bill::where('charge_account_id', '=', $account_id)
            ->where('date', '>=', $start_date)
            ->where('date', '<=', $end_date)
            ->where('is_invoiced', '=', 0)
            ->where('skip_invoicing', '=', 0)
            ->get();

        $invoice = [
            'account_id' => $account_id,
            'date' => date('Y-m-d')
        ];

        $new = new Invoice();
        $new = $new->create($invoice);

        foreach($bills as $bill) {
            $bill->invoice_id = $new->invoice_id;
            $bill->is_invoiced = 1;

            $bill->save();
        }
    }
}
