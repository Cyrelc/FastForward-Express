<?php
namespace App\Http\Repos;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

use DB;
use App\Account;
use App\Amendment;
use App\Bill;
use App\Invoice;
use App\InvoiceSortOptions;
use App\Payment;
use App\Http\Filters\DateBetween;
use App\Http\Filters\NumberBetween;

class InvoiceRepo {
    public function AdjustBalanceOwing($invoice_id, $amount) {
        $invoice = Invoice::where('invoice_id', $invoice_id)
            ->first();

        $invoice->balance_owing += floatval($amount);

        $invoice->save();

        return $invoice;
    }

    public function AssignBillToInvoice($invoiceId, $billId) {
        $bill = Bill::where('bill_id', $billId)->first();
        $invoice = Invoice::where('invoice_id', $invoiceId)->first();
        $account = Account::where('account_id', $bill->charge_account_id)->first();

        if($invoice === null)
            throw new \Exception('Invoice does not exist');
        if($bill->percentage_complete != 100)
            throw new \Exception('Bill must be completed before invoicing');
        if($bill->invoice_id != null)
            throw new \Exception('Bill has already been assigned to an invoice');
        if($bill->charge_account_id != $invoice->account_id && $account->parent_account_id != $invoice->account_id)
            throw new \Exception('Bills can only be assigned to invoices with the same account id or to those with their parent account id; $charge_account_id = ' . $bill->charge_account_id . ' $invoice_account-id = ' . $invoice->account_id . ' $parent_account_id = ' . $account->parent_account_id);
        if($invoice->finalized === 1)
            throw new \Exception('Invoice has been finalized and may have been released to the customer already - unable to add bill');
        $bill->invoice_id = $invoice->invoice_id;
        $bill->save();

        $invoice = $this->CalculateInvoiceBalances($invoice);
        return $invoice;
    }

    public function CalculateAccountBalanceOwing($account_id) {
        $balance_owing = Invoice::where('account_id', $account_id)
            ->sum('balance_owing');

        return $balance_owing;
    }

    private function CalculateInvoiceBalances($invoice) {
        $account = Account::where('account_id', $invoice->account_id)->first();
        $paymentTotal = Payment::where('invoice_id', $invoice->invoice_id)->sum('amount');
        $amendmentTotal = Amendment::where('invoice_id', $invoice->invoice_id)->sum('amount');
        // Note: effective cost here is used as a catch all - when minimum invoice amount is being used, it will be eqal to that, otherwise
        // it will be equal to $billCost. This prevents having to check which to use for every subsequent calculation
        $billCost = $effectiveCost = Bill::where('invoice_id', $invoice->invoice_id)->get()->sum(function ($bill) { return $bill->amount + $bill->interliner_cost_to_customer;}) + $amendmentTotal;
        if($account->min_invoice_amount != null && $account->min_invoice_amount > $billCost)
            $invoice->min_invoice_amount = $effectiveCost = number_format(round($account->min_invoice_amount, 2), 2, '.', '');
        else
            $invoice->min_invoice_amount = null;

        $invoice->bill_cost = number_format($billCost, 2, '.', '');
        $invoice->discount = number_format(round(($effectiveCost * ($account->discount / 100)), 2), 2, '.', '');
        if ($account->gst_exempt)
            $invoice->tax = number_format(0, 2, '.', '');
        else
            $invoice->tax = number_format(round(($effectiveCost - $invoice->discount) * (float)config('ffe_config.gst') / 100, 2), 2, '.', '');
        $invoice->total_cost = $effectiveCost + $invoice->tax;
        $invoice->balance_owing = $invoice->total_cost - $invoice->discount - $paymentTotal;

        $invoice->save();
        return $invoice;
    }

    public function Create($accountIds, $startDate, $endDate) {
        $invoices = array();

        foreach($accountIds as $accountId) {
            $account = Account::where('account_id', $accountId)->first('parent_account_id');
            $bills = Bill::where('charge_account_id', '=', $accountId)
                        ->whereDate('time_pickup_scheduled', '>=', $startDate)
                        ->whereDate('time_pickup_scheduled', '<=', $endDate)
                        ->where('invoice_id', null)
                        ->where('skip_invoicing', '=', 0)
                        ->where('percentage_complete', 100)
                        ->get();

            if(count($bills) > 0) {
                if($account->has_parent) {
                    if(!array_key_exists($account->parent_account_id, $invoices))
                        $invoices[$account->parent_account_id] = $this->GenerateInvoice($account->parent_account_id, $startDate, $endDate);
                    foreach($bills as $bill) {
                        $bill->invoice_id = $invoices[$account->parent_account_id]->invoice_id;
                        $bill->save();
                    }
                } else {
                    if(!array_key_exists($accountId, $invoices))
                        $invoices[$accountId] = $this->GenerateInvoice($accountId, $startDate, $endDate);
                    foreach($bills as $bill) {
                        $bill->invoice_id = $invoices[$accountId]->invoice_id;
                        $bill->save();
                    }
                }
            }
        }

        foreach($invoices as $key => $value) {
            $invoices[$key] = $this->CalculateInvoiceBalances($value);
        }

        return $invoices;
    }

    public function Delete($invoiceId) {
        $amendments = Amendment::where('invoice_id', $invoiceId)->get();
        $bills = Bill::where('invoice_id', '=', $invoiceId)->get();
        $invoice = Invoice::where('invoice_id', '=', $invoiceId)->first();
        $payments = Payment::where('invoice_id', $invoiceId)->get();

        if(sizeof($payments) != 0)
            throw new \Exception('Unable to delete invoice: payments have already been made');

        foreach($amendments as $amendment)
            $this->DeleteAmendment($amendment->amendment_id);

        foreach($bills as $bill) {
            $bill->invoice_id = null;

            $bill->save();
        }

        $invoice->delete();
        return;
    }

    public function DeleteAmendment($amendmentId) {
        $amendment = Amendment::where('amendment_id', $amendmentId)->first();
        $invoice = $this->GetById($amendment->invoice_id);

        $amendment->delete();
        $this->CalculateInvoiceBalances($invoice);
        return;
    }

    public function GenerateInvoice($accountId, $startDate, $endDate) {
        $invoice = [
            'account_id' => $accountId,
            'bill_start_date' => $startDate,
            'bill_end_date' => $endDate,
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

    public function GetAmendmentById($amendmentId) {
        $amendment = Amendment::where('amendment_id', $amendmentId);

        return $amendment->first();
    }

    public function GetAmendmentsByInvoiceId($invoiceId) {
        $amendments = Amendment::where('invoice_id', $invoiceId);

        return $amendments->get();
    }

    public function GetById($id) {
        $invoice = Invoice::where('invoice_id', '=', $id)->first();

        return $invoice;
    }

    public function GetOutstandingByAccountId($account_id) {
        $invoices = Invoice::where('account_id', $account_id)
            ->where('balance_owing', '>', '0')
            ->select(
                'balance_owing',
                'bill_end_date',
                'invoice_id',
                DB::raw('format(total_cost, 2) as total_cost')
            );

        return $invoices->get();
    }

    public function GetSortOptions() {
        $sortOptions = InvoiceSortOptions::all();

        return $sortOptions;
    }

    public function InsertAmendment($amendment) {
        $new = new Amendment;

        $new->create($amendment);
        $invoice = $this->GetById($amendment['invoice_id']);

        $this->CalculateInvoiceBalances($invoice);

        return;
    }

    public function ListAll() {
        $invoices = Invoice::leftJoin('accounts', 'accounts.account_id', '=', 'invoices.account_id')
            ->leftjoin('bills', 'bills.invoice_id', '=', 'invoices.invoice_id')
            ->leftjoin('payments', 'payments.invoice_id', '=', 'invoices.invoice_id')
            ->select(
                'invoices.invoice_id',
                'accounts.account_id',
                'accounts.name as account_name',
                'account_number',
                'invoices.date as date_run',
                'bill_start_date',
                'bill_end_date',
                'balance_owing',
                'bill_cost',
                'total_cost',
                'finalized',
                DB::raw('count(distinct bills.bill_id) as bill_count'),
                DB::raw('count(distinct payments.payment_id) as payment_count')
            )->groupBy('invoices.invoice_id');

        $filteredInvoices = QueryBuilder::for($invoices)
            ->allowedFilters([
                AllowedFilter::exact('account_id', 'invoices.account_id'),
                'account_number',
                AllowedFilter::custom('balance_owing', new NumberBetween),
                AllowedFilter::custom('bill_end_date', new DateBetween),
                AllowedFilter::custom('date_run', new DateBetween, 'invoices.date'),
                AllowedFilter::exact('invoice_id'),
                AllowedFilter::exact('finalized')
            ]);

        return $filteredInvoices->get();
    }

    public function RemoveBillFromInvoice($billId) {
        $bill = Bill::where('bill_id', $billId)->first();
        if($bill->invoice_id === null)
            throw new \Exception('Bill has not been invoiced - Invalid request');

        $invoice = Invoice::where('invoice_id', $bill->invoice_id)->first();
        $bill->invoice_id = null;

        $bill->save();
        $invoice = $this->CalculateInvoiceBalances($invoice);

        return $invoice;
    }

    public function toggleFinalized($invoiceId) {
        $invoice = $this->GetById($invoiceId);
        $invoice->finalized = !filter_var($invoice->finalized, FILTER_VALIDATE_BOOLEAN);

        $invoice->save();
    }
}
