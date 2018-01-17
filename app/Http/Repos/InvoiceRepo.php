<?php
namespace App\Http\Repos;

use App\Account;
use App\Bill;
use App\Invoice;
use App\AccountInvoiceSortEntries;
use App\InvoiceSortOptions;

class InvoiceRepo {
    public function ListAll() {
        $invoices = Invoice::All();

        return $invoices;
    }

    public function GetById($id) {
        $invoice = Invoice::where('invoice_id', '=', $id)->first();

        return $invoice;
    }

    public function GetSortOrderById($id) {
        $account_sort_options = AccountInvoiceSortEntries::where('account_id', '=', $id)->orderBy('priority', 'asc')->get();

        $sort_options = [];
        if(count($account_sort_options) > 0) {
            $count = 0;
            foreach($account_sort_options as $option) {
                //TODO - handle custom sort field slightly differently
                $current = InvoiceSortOptions::where('invoice_sort_option_id', $option->invoice_sort_option_id)->first();
                $current->priority = $option->priority;
                $current->subtotal = $option->subtotal;
                array_push($sort_options, $current);
            }
        } else {
            $sort_options = InvoiceSortOptions::All();
            $count = 0;
                //TODO - handle custom sort field slightly differently
                foreach($sort_options as $option) {
                $option->priority = $option->count;
                $option->subtotal = false;
                $count++;
            }
        }

        return $sort_options;
    }

    public function StoreSortOrder($req, $id) {
        $account_invoice_sort_options = AccountInvoiceSortEntries::where('account_id', '=', $id)->orderBy('priority', 'asc')->get();

        $sort_options = InvoiceSortOptions::All();
        foreach($sort_options as $option) {
            //If the field was submitted as a sort option
            if($req->input($option->database_field_name) !== null) {
                //If the account previously had that sort option set, update it 
                $existing_sort_option = AccountInvoiceSortEntries::where('account_id', $id)->where('invoice_sort_option_id', $option->invoice_sort_option_id)->first();
                if(isset($existing_sort_option)){
                    $existing_sort_option->priority = $req->input($option->database_field_name);
                    if($option->can_be_subtotaled) {
                        $existing_sort_option->subtotal = !empty($req->input('subtotal_' . $option->database_field_name));
                    }
                    $existing_sort_option->save();
                //otherwise create it
                } else {
                    $temp = [
                        'account_id' => $id,
                        'invoice_sort_option_id' => $option->invoice_sort_option_id,
                        'priority' => $req->input($option->database_field_name)
                    ];
                    if($option->can_be_subtotaled)
                        $temp['subtotal'] = !empty($req->input('subtotal_' . $option->database_field_name));
                    $new = new AccountInvoiceSortEntries();
                    $new->create($temp);
                }
            }
        }
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

        $account = Account::where('account_id', '=', $account_id)->first();

        $bill_cost = 0;
        foreach($bills as $bill) {
            $bill_cost += $bill->amount;
            $bill_cost += $bill->interliner_amount;
        }
        //TODO: use variable tax cost rather than hard coded
        //TODO: fuel surcharge logic

        $bill_cost = number_format(round($bill_cost, 2), 2, '.', '');
        $discount = number_format(round(($bill_cost * $account->discount), 2), 2, '.', '');
        if ($account->gst_exempt)
            $tax = number_format(0, 2, '.', '');
        else
            $tax = number_format(round(($bill_cost - $discount) * .05, 2), 2, '.', '');

        $total_cost = number_format(round($bill_cost - $discount + $tax, 2), 2, '.', '');

        $invoice = [
            'account_id' => $account_id,
            'date' => date('Y-m-d'),
            'bill_cost' => $bill_cost,
            'discount' => $discount,
            'tax' => $tax,
            'total_cost' => $total_cost,
            'balance_owing' => $total_cost
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
