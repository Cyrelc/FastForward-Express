<?php

namespace App\Http\Collectors;

class AccountCollector {
    public function Collect($req, $shippingId, $billingId, $accountPermissions) {
        $advancedArray = [];
        if($accountPermissions['editAdvanced']) {
            $canBeParent = filter_var($req->can_be_parent, FILTER_VALIDATE_BOOLEAN);

            $parentAccountId = $req->parent_account_id;
            if($canBeParent || $req->parent_account_id === "")
                $parentAccountId = null;

            $ratesheetId = $req->ratesheet_id;
            if($req->ratesheet_id == '')
                $ratesheetId = null;

            $advancedArray = [
                'active'=>true,
                'account_number'=>$req->account_number,
                'can_be_parent'=>$canBeParent,
                'discount'=>$req->discount === '' ? 0 : $req->discount,
                'gst_exempt'=>filter_var($req->is_gst_exempt, FILTER_VALIDATE_BOOLEAN),
                'min_invoice_amount'=>$req->min_invoice_amount == '' ? 0 : $req->min_invoice_amount,
                'parent_account_id'=>$parentAccountId,
                'ratesheet_id'=>$ratesheetId,
                'start_date'=>(new \DateTime($req->input('start_date')))->format('Y-m-d'),
            ];
        }

        $basicArray = [
            'billing_address_id'=>$billingId,
            'name'=>$req->account_name,
            'shipping_address_id'=>$shippingId,
        ];

        $invoicingArray = [
            'custom_field'=>$req->custom_field === '' ? null : $req->custom_field,
            'invoice_interval'=>$req->invoice_interval,
            'invoice_comment'=>$req->invoice_comment,
            'invoice_sort_order'=>json_encode($req->invoice_sort_order),
            'is_custom_field_mandatory'=>filter_var($req->is_custom_field_mandatory, FILTER_VALIDATE_BOOLEAN),
            'send_bills'=>filter_var($req->send_bills, FILTER_VALIDATE_BOOLEAN),
            'send_email_invoices'=>filter_var($req->send_email_invoices, FILTER_VALIDATE_BOOLEAN),
            'send_paper_invoices'=>filter_var($req->send_paper_invoices, FILTER_VALIDATE_BOOLEAN),
            'show_invoice_line_items'=>filter_var($req->show_invoice_line_items, FILTER_VALIDATE_BOOLEAN)
        ];

        return array_merge(
            ['account_id'=>$req->account_id],
            $accountPermissions['editAdvanced'] ? $advancedArray : [],
            $accountPermissions['editBasic'] ? $basicArray : [],
            $accountPermissions['editInvoicing'] ? $invoicingArray : []
        );
    }
}

