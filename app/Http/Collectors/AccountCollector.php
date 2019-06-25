<?php

namespace App\Http\Collectors;


class AccountCollector {
    public function Collect($req, $billing_id, $delivery_id) {
        $hasDiscount = isset($req->giveDiscount) && $req->input('discount') > 0;

        return [
            'ratesheet_id'=>$req->ratesheet_id === '' ? null : $req->ratesheet_id,
            'billing_address_id'=>$billing_id,
            'shipping_address_id'=>$delivery_id,
            'account_number'=>$req->account_number,
            'invoice_interval'=>$req->invoice_interval,
            'stripe_id'=>40,
            'name'=>$req->account_name,
            'start_date'=>(new \DateTime($req->input('start_date')))->format('Y-m-d'),
            'send_bills'=>isset($req->send_bills),
            'send_invoices' => isset($req->send_invoices),
            'has_parent'=>$req->parent_account_id == null ? false : true,
            'parent_account_id'=>$req->parent_account_id == null ? null : $req->parent_account_id,
            'has_discount' => $req->discount == null ? false : true,
            'discount'=> $req->discount,
            'gst_exempt'=>isset($req->is_gst_exempt),
            'charge_interest'=>isset($req->charge_interest),
            'can_be_parent'=>isset($req->can_be_parent),
            'active'=>true,
            'uses_custom_field' => $req->custom_tracker == '' ? false : true,
            'custom_field' => $req->custom_tracker,
            'fuel_surcharge' => $req->fuel_surcharge,
            'min_invoice_amount' => $req->min_invoice_amount,
            'use_parent_ratesheet' => isset($req->use_parent_ratesheet)
        ];
    }
}

