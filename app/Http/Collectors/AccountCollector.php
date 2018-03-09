<?php

namespace App\Http\Collectors;


class AccountCollector {
    public function Collect($req, $billing_id, $delivery_id) {

        $hasDiscount = isset($req->giveDiscount) && $req->input('discount') > 0;

        return [
            'rate_type_id'=>1,
            'billing_address_id'=>$billing_id,
            'shipping_address_id'=>$delivery_id,
            'account_number'=>$req->input('account-number'),
            'invoice_interval'=>$req->input('invoice-interval'),
            'stripe_id'=>40,
            'name'=>$req->input('name'),
            'start_date'=>(new \DateTime($req->input('start-date')))->format('Y-m-d'),
            'send_bills'=>isset($req->send_bills),
            'send_invoices' => isset($req->send_invoices),
            'has_parent'=>isset($req->isSubLocation),
            'parent_account_id'=>isset($req->isSubLocation) ? $req->input('parent-account-id') : null,
            'has_discount' => $hasDiscount,
            'discount'=> $hasDiscount ? $req->input('discount') : 0,
            'gst_exempt'=>isset($req->isGstExempt),
            'charge_interest'=>isset($req->chargeInterest),
            'can_be_parent'=>isset($req->canBeParent),
            'active'=>true,
            'uses_custom_field' => isset($req->useCustomField),
            'custom_field' => isset($req->useCustomField) ? $req->input('custom-tracker') : null,
            'fuel_surcharge' => isset($req->hasFuelSurcharge) ? $req->input('fuel-surcharge') / 100 : 0,
            'min_invoice_amount' => isset($req->has_min_invoice_amount) ? $req->min_invoice_amount : 0
        ];
    }
}
