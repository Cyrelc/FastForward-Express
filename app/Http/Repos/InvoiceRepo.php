<?php
namespace App\Http\Repos;

use DB;
use App\Account;
use App\Bill;
use App\Invoice;
use App\AccountInvoiceSortEntries;
use App\InvoiceSortOptions;

class InvoiceRepo {

    public function AdjustBalanceOwing($invoice_id, $amount) {
        $invoice = Invoice::where('invoice_id', $invoice_id)
            ->first();

        $invoice->balance_owing += $amount;

        $invoice->save();

        return $invoice;
    }

    public function ListAll() {
        $invoices = Invoice::leftJoin('accounts', 'accounts.account_id', '=', 'invoices.account_id')
            ->select('invoices.invoice_id',
                'accounts.account_id',
                'accounts.name as account_name',
                'date',
                'balance_owing',
                'bill_cost',
                'total_cost',
                DB::raw('(select count(*) from bills where invoice_id = invoices.invoice_id) as bill_count'));

        return $invoices->get();
    }

    public function CalculateAccountBalanceOwing($account_id) {
        $balance_owing = Invoice::where('account_id', $account_id)
            ->sum('balance_owing');

        return $balance_owing;
    }

    public function GetById($id) {
        $invoice = Invoice::where('invoice_id', '=', $id)->first();

        return $invoice;
    }

    public function GetOutstandingByAccountId($account_id) {
        $invoices = Invoice::where('account_id', $account_id)
            ->where('balance_owing', '>', '0')
            ->select('invoice_id',
                'date',
                'balance_owing');

        return $invoices->get();
    }

    public function GetSubtotalById($account_id) {
        $subtotal = AccountInvoiceSortEntries::where('account_id', $account_id)
                ->where('subtotal', true)
                ->join('invoice_sort_options', 'invoice_sort_options.invoice_sort_option_id', '=', 'account_invoice_sort_entries.invoice_sort_option_id')
                ->first();

        return isset($subtotal) ? $subtotal : NULL;
    }

    public function GetSortOrderById($id) {
        $accountRepo = new AccountRepo();

        $sort_options = InvoiceSortOptions::leftJoin('account_invoice_sort_entries', function($join) use ($id) {
                    $join->on('account_invoice_sort_entries.invoice_sort_option_id', '=', 'invoice_sort_options.invoice_sort_option_id')
                        ->where('account_invoice_sort_entries.account_id', '=', $id);
                })
                ->orderBy('priority', 'asc')
                ->get();

        $account = $accountRepo->GetById($id);

        foreach($sort_options as $key => $option) {
            $contingent_field = $option->contingent_field;
            if($contingent_field != NULL && $account->$contingent_field == false) {
                unset($sort_options[$key]);
                continue;
            }
            if($option->database_field_name == 'charge_reference_value')
                $sort_options[$key]->friendly_name = $account->custom_field;
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
                    if($option->database_field_name == 'charge_reference_value' && Account::select('uses_custom_field')->where('account_id', $id)->first()->uses_custom_field == 0) {
                        $existing_sort_option->delete();
                    } else {
                        $existing_sort_option->priority = $req->input($option->database_field_name);
                        if($option->can_be_subtotaled) {
                            $existing_sort_option->subtotal = !empty($req->input('subtotal_' . $option->database_field_name));
                        }
                        $existing_sort_option->save();
                    }
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
        $invoices = array();

        foreach($account_ids as $account_id) {
            $account = Account::where('account_id', $account_id)->first(['has_parent', 'parent_account_id']);
            $bills = Bill::where('charge_account_id', '=', $account_id)
                        ->where('time_pickup_scheduled', '>=', $start_date)
                        ->where('time_pickup_scheduled', '<=', $end_date)
                        ->where('invoice_id', null)
                        ->where('skip_invoicing', '=', 0)
                        ->where('percentage_complete', 1)
                        ->get();

            if(count($bills) > 0) {
                if($account->has_parent) {
                    if(!array_key_exists($account->parent_account_id, $invoices))
                        $invoices[$account->parent_account_id] = $this->GenerateInvoice($account->parent_account_id);
                    foreach($bills as $bill) {
                        $bill->invoice_id = $invoices[$account->parent_account_id]->invoice_id;
                        $bill->save();
                    }
                } else {
                    if(!array_key_exists($account_id, $invoices))
                        $invoices[$account_id] = $this->GenerateInvoice($account_id);
                    foreach($bills as $bill) {
                        $bill->invoice_id = $invoices[$account_id]->invoice_id;
                        $bill->save();
                    }
                }
            }
        }

        foreach($invoices as $key => $value) {
            //TODO: use variable tax cost rather than hard coded
            //TODO: fuel surcharge logic
            $account_repo = new AccountRepo();
            $invoice = Invoice::where('invoice_id', $value->invoice_id)->first();
            $account = $account_repo->GetById($invoice->account_id);
            $bill_cost = Bill::where('invoice_id', $invoice->invoice_id)->get()->sum(function ($bill) { return $bill->amount + $bill->interliner_amount;});
            if($account->min_invoice_amount != null && $account->min_invoice_amount > $bill_cost)
                $bill_cost = $account->min_invoice_amount;
            $invoice->bill_cost = number_format(round($bill_cost, 2), 2, '.', '');
            $invoice->discount = number_format(round(($bill_cost * $account->discount), 2), 2, '.', '');
            if ($account->gst_exempt)
                $invoice->tax = number_format(0, 2, '.', '');
            else
                $invoice->tax = number_format(round(($invoice->bill_cost - $invoice->discount) * .05, 2), 2, '.', '');

            $invoice->total_cost = $invoice->balance_owing = number_format(round($invoice->bill_cost - $invoice->discount + $invoice->tax, 2), 2, '.', '');

            $invoice->save();
        }
        return $invoices;
    }

    public function Delete($invoiceId) {
        $bills = Bill::where('invoice_id', '=', $invoiceId)->get();
        $invoice = Invoice::where('invoice_id', '=', $invoiceId)->first();

        foreach($bills as $bill) {
            $bill->invoice_id = null;

            $bill->save();
        }

        $invoice->delete();
        return;
    }

    public function GenerateInvoice($account_id) {
        $invoice = [
            'account_id' => $account_id,
            'date' => date('Y-m-d'),
            'bill_cost' => 0,
            'discount' => 0,
            'tax' => 0,
            'total_cost' => 0,
            'balance_owing' => 0
        ];
        $new = new Invoice();
        return $new->create($invoice);
    }
}
