<?php

namespace App\Http\Collectors;

class AccountCollector {
    public function Collect($req, $billingId, $shippingId) {
        $canBeParent = filter_var($req->can_be_parent, FILTER_VALIDATE_BOOLEAN);
        $useParentRatesheet = filter_var($req->use_parent_ratesheet, FILTER_VALIDATE_BOOLEAN);
        $ratesheetId = $req->ratesheet_id;
        if($useParentRatesheet)
            $ratesheetId = null;
        if($req->ratesheet_id == '')
            $ratesheetId = null;

        return [
            'account_id'=>$req->account_id == '' ? null : $req->account_id,
            'active'=>true,
            'account_number'=>$req->account_number,
            'billing_address_id'=>$billingId,
            'can_be_parent'=>$canBeParent,
            'custom_field'=>$req->custom_field === '' ? null : $req->custom_field,
            'discount'=>$req->discount === '' ? 0 : $req->discount,
            'gst_exempt'=>filter_var($req->is_gst_exempt, FILTER_VALIDATE_BOOLEAN),
            'invoice_interval'=>$req->invoice_interval,
            'invoice_comment'=>$req->invoice_comment,
            'invoice_sort_order'=>json_encode($req->invoice_sort_order),
            'min_invoice_amount'=>$req->min_invoice_amount == '' ? 0 : $req->min_invoice_amount,
            'name'=>$req->account_name,
            'parent_account_id'=>$canBeParent ? null : $req->parent_account_id,
            'ratesheet_id'=>$ratesheetId,
            'send_bills'=>filter_var($req->send_bills, FILTER_VALIDATE_BOOLEAN),
            'send_email_invoices'=>filter_var($req->send_email_invoices, FILTER_VALIDATE_BOOLEAN),
            'send_paper_invoices'=>filter_var($req->send_paper_invoices, FILTER_VALIDATE_BOOLEAN),
            'shipping_address_id'=>$shippingId,
            'start_date'=>(new \DateTime($req->input('start_date')))->format('Y-m-d'),
            'use_parent_ratesheet'=>$canBeParent ? null : $useParentRatesheet
        ];
    }
}

