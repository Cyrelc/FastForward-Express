<?php

namespace App\Http\Services;

use TCPDF;
use App\Http\Models\Invoice;

class FFEpdf extends TCPDF {
    public function Header() {
        $this->y = 10;
        $this->setCellPaddings(2,2,2,2);
        $this->SetFont('times', 'B', 20);
        $name = 'Fast Forward Express Ltd.';
        $this->MultiCell(130, 0, $name, 0, 'L', false, 1);
        $this->SetFont('times', '', 12);
        $text = 'Serving Edmonton, St. Albert, and the surrounding area since 1992';
        $this->MultiCell(130, 0, $text, 0, 'L', false, 0);
        $this->SetFont('times', '', 11);
        $address = 'Box 11117 <br /> Edmonton, Ab <br /> T5J 2K4';
        $this->MultiCell(0, 0, $address, 0, 'L', false, 1, '150', '15', true, 0, true);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('times', '', 11);
        $this->MultiCell(50, 15, 'Phone: (780)-458-1074', 0, 'L', false, 0);
        $this->MultiCell(80, 15, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 'C', false, 0);
        $this->MultiCell(50, 15, '<a href="www.fastforwardexpress.com">www.fastforwardexpress.com</a>', 0, 'R', false, 0, '', '', true, 0, true);
        // $this->footer_text_color(array(0,64,0), array(0,64,128));
        // $this->SetMargin(PDF_MARGIN_FOOTER);
    }

    public function GetHeaderHeight() {
        return 35;
    }
}

class InvoicePDFfactory{
    function generateInvoice($invoice_id){
        $invoice_model_factory = new Invoice\InvoiceModelFactory();

        $model = $invoice_model_factory->GetById($invoice_id);

        $pdf = new FFEpdf();

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Username who requested PDF goes here');
        $pdf->SetTitle($model->parents[0]->name . '-' . $model->invoice->date);
        $pdf->SetSubject('Invoice');
        $pdf->SetKeywords('invoice');

        // setup body
        $pdf->SetMargins(PDF_MARGIN_LEFT, $pdf->GetHeaderHeight(), PDF_MARGIN_RIGHT);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('times', 'B', 16, '', true);
        
        $pdf->AddPage();
        $company = '<strong>Account # ' . $model->parents[0]->account_number . '<br/>';
        for($i = count($model->parents) - 1; $i >= 0; $i--) {
            $company .= $model->parents[$i]->name . '<br/>';
        }
        $pdf->writeHTML($company . '</strong>', true, false, true, false, 'C');

        $pdf->SetFont('times', '', 12, '', true);
        $pdf->Line(PDF_MARGIN_LEFT, $pdf->GetHeaderHeight(), 210 - PDF_MARGIN_RIGHT, $pdf->GetHeaderHeight(), array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(220, 220, 220)));
        $pdf->setCellPadding(2, 2, 2, 2);
        $pdf->MultiCell(45, 0, 'Invoice Number: <br />' . $model->invoice->invoice_id, 1, 'C', false, 0, '', '', true, 0, true);
        $pdf->MultiCell(45, 0, 'Date: <br/>' .  $model->invoice->date, 1, 'C', false, 0, '', '', true, 0, true);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->MultiCell(45, 0, 'Invoice Total: <br/>' . $model->invoice->balance_owing, 1, 'C', true, 0, '', '', true, 0, true);
        $pdf->SetFillColor(244, 116, 40);
        $pdf->MultiCell(45, 0, 'Account Balance: <br/>' . $model->invoice->balance_owing, 1, 'C', true, 1, '', '', true, 0, true);
        // TODO - add account balance to invoice layout model
        // $pdf->MultiCell(40, 0, 'Account Balance: <br/>' . $model->account_balance, 1, 'C', true, 0, '', '', true, 0, true);
        $shipping_address = $model->parents[0]->shipping_address->street . '<br/>';
        if (isset($model->parents[0]->shipping_address->street2) && $model->parents[0]->shipping_address->street2 != '')
            $shipping_address .= $model->parents[0]->shipping_address->street2 . '<br/>';
        $shipping_address .= $model->parents[0]->shipping_address->city . ', ' . $model->parents[0]->shipping_address->state_province . '<br/>' . $model->parents[0]->shipping_address->zip_postal;
        $billing_address;
        if(isset($model->parents[0]->billing_address_id) && $model->parents[0]->billing_address_id != '') {
            $billing_address = $model->parents[0]->billing_address->street . '<br/>';
            if(isset($model->parents[0]->billing_address->street2) && $model->parents[0]->billing_address->street2 != '')
                $billing_address .= $model->parents[0]->billing_address->street2 . '<br/>';
            $billing_address .= $model->parents[0]->billing_address->city . ', ' . $model->parents[0]->billing_address->state_province . '<br/>' . $model->parents[0]->billing_address->zip_postal;
        } else
            $billing_address = $shipping_address;
        $pdf->MultiCell(90, 0, '<strong>Billing Address</strong><br/>' . $billing_address, 0, 'L', false, 0, '', '', true, 0, true);
        $pdf->MultiCell(90, 0, '<strong>Shipping Address</strong><br/>' . $shipping_address, 0, 'R', false, 1, '', '', true, 0, true);

        $pdf->Ln(2);

        $table = '<style> 
                table {
                    page-break-inside: avoid;
                }
                .header {
                    border-bottom: 1px #dcdcdc;
                } 
                td {
                    height:35px;
                } 
                tr.subtotal {
                    background-color: #d6e0f5;
                }
                td.amount {
                    text-align:right;
                }
                </style>
                <table><thead><tr>';
        foreach($model->headers as $key => $value) {
            $table .= '<td class="header">' . $key . '</td>';
        }
        $table .= '</tr></thead><tbody>';
        foreach($model->table as $line) {
            $table .= $line->is_subtotal ? '<tr class="subtotal">' : '<tr>';
            foreach($model->headers as $header)
                    $table .= ($header == 'amount' ? '<td class="amount">' : '<td>') . $line->$header . '</td>';
            $table .= '</tr>';
        }
        $table .= '</tbody></table>';
        // dd($table);
        $pdf->writeHTML($table);
        
        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.
        return $pdf->Output($model->parents[0]->name . '-' . $model->invoice->date, 'I');
    }
}
?>
