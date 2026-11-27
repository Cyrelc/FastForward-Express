<?php
namespace App\Http\Repos;

use App\Models\Charge;
use App\Models\LineItem;
use Illuminate\Support\Facades\DB;

class LineItemRepo {
    public function CountUninvoicedByInvoiceSettings($invoice) {
        $billCount = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'charges.bill_id', '=', 'bills.bill_id')
            ->where('invoice_id', null)
            ->where('charge_account_id', $invoice->account_id)
            ->whereDate('time_pickup_scheduled', '>=', $invoice->bill_start_date)
            ->whereDate('time_pickup_scheduled', '<=', $invoice->bill_end_date)
            ->distinct('charges.bill_id');

        return $billCount->count();
    }

    public function Delete($lineItemId) {
        $lineItem = LineItem::find($lineItemId);
        // A chargeback_id means the charge has been (or is being) deducted from an employee's pay
        if($lineItem->pickup_manifest_id || $lineItem->delivery_manifest_id || $lineItem->invoice_id || $lineItem->chargeback_id) {
            abort(403, 'Unable to delete line item after it has been invoiced, manifested or charged back');
        }

        $lineItem->delete();
        return;
    }

    public function GetAmendmentsByBillAndInvoiceId($billId, $invoiceId) {
        $amendments = LineItem::where('invoice_id', $invoiceId)
            ->leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->where('bill_id', $billId)
            ->where('amendment_number', '>', 0);

        return $amendments->get();
    }

    public function GetByBillAndInvoiceId($billId, $invoiceId) {
        $lineItems = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->where('bill_id', $billId)
            ->where('invoice_id', $invoiceId)
            ->where('amendment_number', null);

        return $lineItems->get();
    }

    public function GetByBillId($billId) {
        $lineItems = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->where('bill_id', $billId)
            ->leftJoin('invoices', 'invoices.invoice_id', 'line_items.invoice_id');

        return $lineItems->get();
    }

    public function GetByChargeId($chargeId) {
        $lineItems = LineItem::where('charge_id', $chargeId)
            ->leftJoin('invoices', 'invoices.invoice_id', 'line_items.invoice_id')
            ->leftJoin('selections', 'selections.value', 'line_items.type')
            ->leftJoin('chargebacks', 'chargebacks.chargeback_id', 'line_items.chargeback_id')
            ->select(
                'amendment_number',
                'line_items.charge_id',
                // Employee charges settle through a chargeback rather than an invoice. The chargeback the line item
                // links to is the schedule; each deduction is a copy carrying the manifest it was taken on, linked
                // back by source_chargeback_id. Older copies have no such link, so they are matched by bill and
                // employee instead (a bill can be charged back to several employees, each for their own part).
                'line_items.chargeback_id',
                'chargebacks.count_remaining as chargeback_count_remaining',
                'chargebacks.continuous as chargeback_continuous',
                DB::raw('(select group_concat(distinct deductions.manifest_id order by deductions.manifest_id)
                    from chargebacks as deductions
                    where deductions.manifest_id is not null
                    and (deductions.source_chargeback_id = line_items.chargeback_id
                        or (deductions.source_chargeback_id is null
                            and deductions.employee_id = chargebacks.employee_id
                            and deductions.bill_id = (select charges.bill_id from charges where charges.charge_id = line_items.charge_id)))
                ) as chargeback_manifest_ids'),
                'line_items.delivery_driver_id',
                'line_items.delivery_manifest_id',
                'line_items.driver_amount',
                'finalized as invoice_is_finalized',
                'invoices.invoice_id',
                'line_item_id',
                'line_items.name',
                'line_items.type',
                'line_items.pickup_driver_id',
                'line_items.pickup_manifest_id',
                'line_items.price',
                'selections.name as friendly_type_name'
            );

        return $lineItems->get();
    }

    public function GetByid($lineItemId) {
        $lineItem = LineItem::where('line_item_id', $lineItemId);

        return $lineItem->first();
    }

    public function GetDriverTotalByManifestId($manifestId) {
        $total = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
                ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
                ->where('pickup_manifest_id', $manifestId)
                ->orWhere('delivery_manifest_id', $manifestId)
                ->select(
                    DB::raw('sum(case ' .
                    'when pickup_manifest_id = ' . $manifestId . ' and delivery_manifest_id = ' . $manifestId . ' then round(driver_amount * pickup_driver_commission, 2) + round(driver_amount * delivery_driver_commission, 2) ' .
                    'when pickup_manifest_id = ' . $manifestId . ' then round(driver_amount * pickup_driver_commission, 2) ' .
                    'when delivery_manifest_id  = ' . $manifestId . ' then round(driver_amount * delivery_driver_commission, 2) end) as total'))
                ->pluck('total');

        return $total[0];
    }

    public function Insert($lineItem) {
        $new = new LineItem;

        return ($new->create($lineItem));
    }

    public function InvoiceForAccount($invoice, $accountId = null, $asAmendment = false) {
        $startDate = (new \DateTime($invoice->bill_start_date))->format('Y-m-d');
        $endDate = (new \DateTime($invoice->bill_end_date))->format('Y-m-d');

        $lineItems = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->where('charge_account_id', $accountId ? $accountId : $invoice->account_id)
            ->whereDate('time_pickup_scheduled', '>=', $startDate)
            ->whereDate('time_pickup_scheduled', '<=', $endDate)
            ->where('invoice_id', null)
            ->where('skip_invoicing', 0)
            ->where('percentage_complete', 100)
            ->where('price', '!=', 0)
            ->get();

        $amendmentNumber = $asAmendment ? LineItem::where('invoice_id', $invoice->invoice_id)->max('amendment_number') + 1 : null;

        foreach($lineItems as $lineItem) {
            $lineItem->invoice_id = $invoice->invoice_id;
            $lineItem->amendment_number = $amendmentNumber;
            $lineItem->save();
        }

        return $lineItems;
    }

    public function InvoiceForCharge($invoice, $chargeId) {
        $lineItems = LineItem::leftJoin('charges', 'charges.charge_id', '=', 'line_items.charge_id')
            ->leftJoin('bills', 'bills.bill_id', '=', 'charges.bill_id')
            ->where('line_items.charge_id', $chargeId)
            ->where('invoice_id', null)
            ->where('percentage_complete', 100)
            ->where('price', '!=', 0)
            ->get();

        foreach($lineItems as $lineItem) {
            $lineItem->invoice_id = $invoice->invoice_id;
            // $lineItem->amendmentNumber = $amendmentNumber;
            $lineItem->save();
        }

        return $lineItems;
    }

    public function Update($lineItem) {
        $old = $this->GetById($lineItem['line_item_id']);

        $fields = [
            'amount',
            'delivery_driver_id',
            'delivery_manifest_id',
            'driver_amount',
            'invoice_id',
            'pickup_driver_id',
            'pickup_manifest_id',
            'type'
        ];

        foreach($fields as $field)
            if(array_key_exists($field, $lineItem))
                $old->$field = $lineItem[$field];

        $old->save();
        return $old;
    }

    public function updateAsBill($lineItem) {
        $old = LineItem::find($lineItem['line_item_id']);

        $old->delivery_driver_id = $lineItem['delivery_driver_id'];
        $old->driver_amount = $lineItem['driver_amount'];
        $old->pickup_driver_id = $lineItem['pickup_driver_id'];
        $old->price = $lineItem['price'];
        $old->type = $lineItem['type'];

        return $old->save();
    }
}
