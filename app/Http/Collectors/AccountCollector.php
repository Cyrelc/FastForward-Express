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
            'fuel_surcharge' => isset($req->hasFuelSurcharge) ? $req->input('fuel-surcharge') / 100 : 0
        ];
    }

    public function Remerge($req, $model) {
        if ($req->old("name") !== null)
            $model->account->name = $req->old("name");

        if ($req->old("rate_type_id") !== null)
            $model->account->rate_type_id = $req->old("rate_type_id");

        if ($req->old("account-number") !== null)
            $model->account->account_number = $req->old("account-number");

        if ($req->old("invoice-interval") !== null)
            $model->account->invoice_interval = $req->old("invoice-interval");

        if ($req->old("start-date") !== null)
            $model->account->start_date = strtotime($req->old("start-date"));

        if ($req->old("send-bills") !== null)
            $model->account->send_bills = $req->old("send-bills");

        if ($req->input('parent-account-id') != null && strlen($req->input('parent-account-id')) > 0)
            $model->account->is_master = true;

        if ($req->old("parent-account-id") !== null)
            $model->account->parent_account_id = $req->old("parent-account-id");

        if ($req->input('discount') != null && $req->input('discount') > 0)
            $model->account->gets_discount = true;

        if ($req->old("discount") !== null && $req->old("discount") !== "")
            $model->account->discount = $req->old("discount") / 100;

        if ($req->old("isGstExempt") !== null)
            $model->account->gst_exempt = $req->old("isGstExempt") == "true";

        if ($req->old("shouldChargeInterest") !== null)
            $model->account->charge_interest = $req->old("shouldChargeInterest") == "true";

        if ($req->old("canBeParent") !== null)
            $model->account->can_be_parent = $req->old("canBeParent") == "true";

        if ($req->old("useCustomField") !== null)
            $model->account->uses_custom_field = $req->old("useCustomField") == "true";

        if ($req->old("custom-tracker") !== null)
            $model->account->custom_field = $req->old("custom-tracker");

        if ($req->old("has-fuel-surcharge") !== null && $req->old('has-fuel-surcharge') == 'true' && $req->old("has-fuel-surcharge") !== "")
            $model->account->fuel_surcharge = $req->old("fuel-surcharge") / 100;

        return $model;
    }
}
