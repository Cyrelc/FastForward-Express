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
            'has_parent'=>$req->input('parent-account-id') == null ? false : true,
            'parent_account_id'=>$req->input('parent-account-id') == null ? null : $req->input('parent-account-id'),
            'has_discount' => $req->discount == null ? false : true,
            'discount'=> $req->discount,
            'gst_exempt'=>isset($req->isGstExempt),
            'charge_interest'=>isset($req->chargeInterest),
            'can_be_parent'=>isset($req->canBeParent),
            'active'=>true,
            'uses_custom_field' => $req->input('custom-tracker') == '' ? false : true,
            'custom_field' => $req->input('custom-tracker'),
            'fuel_surcharge' => $req->input('fuel-surcharge'),
            'min_invoice_amount' => $req->min_invoice_amount
        ];
    }
}
