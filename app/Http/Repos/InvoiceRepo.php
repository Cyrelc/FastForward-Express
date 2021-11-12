<?php
namespace App\Http\Repos;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

use DB;
use App\Account;
use App\Bill;
use App\Invoice;
use App\InvoiceSortOptions;
use App\LineItem;
use App\Payment;
use App\Http\Filters\DateBetween;
use App\Http\Filters\NumberBetween;

class InvoiceRepo {
    public function AdjustBalanceOwing($invoiceId, $amount) {
        $invoice = Invoice::where('invoice_id', $invoiceId)
            ->first();

        $invoice->balance_owing += floatval($amount);

        if($invoice->balance_owing == 0) {
            $lineItemRepo = new LineItemRepo();

            $lineItemRepo->PayOffLineItemsByInvoiceId($invoiceId);
        }

        $invoice->save();

        return $invoice;
    }

    public function AttachLineItem($lineItemId, $invoiceId) {
        $lineItemRepo = new LineItemRepo();

        $invoice = $this->GetById($invoiceId);
        $lineItem = $lineItemRepo->GetById($lineItemId);

        if($invoice === null)
            throw new \Exception('Invoice does not exist');
        if($invoice->finalized)
            throw new \Exception('Unable to detach from finalized invoice');
        if($lineItem->charge->bill->percentage_complete != 100)
            throw new \Exception('Bill must be completed before invoicing');
        if($lineItem->invoice_id != null)
            throw new \Exception('Line Item has already been assigned to an invoice');
        if($lineItem->charge->charge_account_id != $invoice->account_id && $lineItem->charge->account->parent_account_id != $invoice->account_id)
            throw new \Exception('Line Item must be assigned to an invoice with the same account id or to those with their parent account id matching the charge;');

        $lineItem->invoice_id = $invoiceId;
        if($invoice->finalized) {
            $amendmentNumber = LineItem::where('invoice_id', $invoiceId)->max('amendment_number');
            if($amendmentNumber == null)
                $lineItem->amendment_number = 0;
            else
                $lineItem->amendment_number = $amendmentNumber + 1;
        }

        $lineItem->save();
        $this->CalculateInvoiceBalances($invoice);

        return $lineItem;
    }

    public function CalculateAccountBalanceOwing($accountId) {
        $balanceOwing = Invoice::where('account_id', $accountId)
            ->sum('balance_owing');

        return $balanceOwing;
    }

    public function Create($accountIds, $startDate, $endDate) {
        $accountRepo = new AccountRepo();
        $lineItemRepo = new LineItemRepo();

        $invoices = array();

        foreach($accountIds as $accountId) {
            $account = $accountRepo->GetById($accountId);
            $effectiveAccountId = isset($account->parent_account_id) ? $account->parent_account_id : $account->account_id;

            if(array_key_exists($effectiveAccountId, $invoices))
                $invoice = $invoices[$effectiveAccountId];
            else
                $invoices[$effectiveAccountId] = $invoice = $this->GenerateInvoice($effectiveAccountId, $startDate, $endDate);

            $lineItems = $lineItemRepo->InvoiceLineItems($invoice, $accountId);
        }

        foreach($invoices as $key => $value) {
            $invoices[$key] = $this->CalculateInvoiceBalances($value);
        }

        return $invoices;
    }

    public function Delete($invoiceId) {
        $lineItems = LineItem::where('invoice_id', '=', $invoiceId)->get();
        $invoice = Invoice::where('invoice_id', '=', $invoiceId)->first();
        $payments = Payment::where('invoice_id', $invoiceId)->get();

        if(sizeof($payments) != 0)
            throw new \Exception('Unable to delete invoice: payments have already been made');

        foreach($lineItems as $lineItem) {
            $lineItem->invoice_id = null;

            $lineItem->save();
        }

        $invoice->delete();
        return;
    }

    public function DetachLineItem($lineItemId) {
        $lineItemRepo = new LineItemRepo();
        $lineItem = $lineItemRepo->GetById($lineItemId);

        if($lineItem->invoice_id == null)
            throw new \Exception('Line Item has not been assigned to an invoice');

        $invoice = $this->GetById($lineItem->invoice_id);
        if($invoice->finalized)
            throw new \Exception('Unable to detatch Line Item: Invoice has been finalized and sent to the customer. Please perform the change as an amendment instead');

        $lineItem->invoice_id = null;
        $lineItem->save();
        $this->CalculateInvoiceBalances($invoice);

        return $lineItem;
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
                'total_cost'
            );

        return $invoices->get();
    }

    public function GetSortOptions() {
        $sortOptions = InvoiceSortOptions::all();

        return $sortOptions;
    }

    public function ListAll($myAccounts) {
        $invoices = Invoice::leftJoin('accounts', 'accounts.account_id', '=', 'invoices.account_id')
            ->leftJoin('line_items', 'line_items.invoice_id', '=', 'invoices.invoice_id')
            ->leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftjoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
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

        if($myAccounts)
            $invoices->whereIn('invoices.account_id', $myAccounts);

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

    public function RegatherInvoice($invoice) {
        $accountRepo = new AccountRepo();
        $lineItemRepo = new LineItemRepo();

        $count = 0;
        $account = $accountRepo->GetById($invoice->account_id);
        $children = $accountRepo->GetChildAccountList($account->account_id);
        foreach($children as $child)
            $count += count($lineItemRepo->InvoiceLineItems($invoice, $child->account_id, $invoice->finalized));
        $count += count($lineItemRepo->InvoiceLineItems($invoice, null, $invoice->finalized));

        $this->CalculateInvoiceBalances($invoice);

        return $count;
    }

    public function ToggleFinalized($invoiceId) {
        $invoice = $this->GetById($invoiceId);
        $invoice->finalized = !filter_var($invoice->finalized, FILTER_VALIDATE_BOOLEAN);

        $invoice->save();
    }

    /**
     * Private functions
     */

    private function CalculateInvoiceBalances($invoice) {
        $account = Account::where('account_id', $invoice->account_id)->first();
        $paymentTotal = Payment::where('invoice_id', $invoice->invoice_id)->sum('amount');
        // Note: effective cost here is used as a catch all - when minimum invoice amount is being used, it will be equal to that, otherwise
        // it will be equal to $billCost. This prevents having to check which to use for every subsequent calculation
        $billCost = $effectiveCost = LineItem::where('invoice_id', $invoice->invoice_id)->get()->sum('price');
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

}
