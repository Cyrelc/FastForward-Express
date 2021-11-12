<link rel='stylesheet' type='text/css' href='./css/invoices/invoice_table_pdf.css' />
<link rel='stylesheet' type='text/css' href='/css/invoices/invoice_table_pdf.css' />

<hr/>
<table style='overflow: visible'>
    <td style='width: 40%; text-align: center'>
        <h3>{{$model->parent->account_number}} - {{$model->parent->name}}</h3>
    </td>
    <td class='basic' >
        <h4>Bill Count:<br/><br/>{{$model->invoice->bill_count}}</h4>
    </td>
    <td class='basic' >
        <h4>Invoice Total:<br/><br/>{{'$' . number_format($model->invoice->total_cost, 2)}}</h4>
    </td>
    <td class='warn' >
        <h4>Account Balance:<br/><br/>{{'$' . number_format($model->account_owing, 2)}}</h4>
    </td>
</table>

<div class='header'>
    <table>
        <td width:'30%'>
            <h4>Account Number: {{$model->parent->account_number}}<br/>
                Invoice ID: {{$model->invoice->invoice_id}}<br/>
                Date: {{$model->invoice->bill_end_date}}</h4>
        </td>
        <td width:'40%' style='text-align: center;'>
            <h2>Fast Forward Express Ltd.</h2>
        </td>
        <td class='text-right' width:'30%'>
            <h4>Box 11117<br/>Edmonton, AB<br/>T5J 2K4</h4>
        </td>
    </table>
</div>
<div class='footer'>
    <table>
        <tr>
            <td style='width: 30%'>(780) 458-1074</td>
            <td style='width: 40%; text-align: center;'>Page: <span class='pagenum'></span></td>
            <td style='width: 30%; text-align: right'><a href='www.fastforwardexpress.com'>Fastfex@telus.net</a></td>
        </tr>
        <tr>
            <td style='width: 30%'>GST# 133746107</td>
        </tr>
    </table>
</div>
<table id='addresses'>
    <tr>
        @foreach(['Billing Address' => 'billing_address', 'Shipping Address' => 'shipping_address'] as $name => $address)
        <td class='{{$address == "billing_address" ? 'text-left' : 'text-right' }}'>
            <strong>{{$name}}:</strong><br/>
            {{$model->parent->$address->name}}<br/>
            @foreach(explode(',', $model->parent->$address->formatted) as $addressLine)
                {{ltrim($addressLine)}}<br/>
            @endforeach
        </td>
        @endforeach
    </tr>
</table>
<br/><br/>

<hr/>
@if($model->invoice->finalized === 0)
    <div id="watermark">Invoice Not Finalized</div>
@endif
@if($model->parent->invoice_comment != '')
    <p>{{$model->parent->invoice_comment}}</p><hr/><br/>
@endif
@if($amendmentsOnly == 'false')
    @foreach($model->tables as $table_key => $table)
        <table class='bill_list'>
            <thead>
                <tr>
                    @foreach($table->headers as $key => $value)
                        <td class='{{$value}}'> {{$key}} </td>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($table->bills as $bill)
                    <tr>
                        @foreach($table->headers as $key => $value)
                            @if($value == 'amount')
                                @if($showLineItems == 'true')
                                    <td>
                                        <table style='border: none;'>
                                            @foreach($bill->line_items as $lineItem)
                                                <tr>
                                                    <td style='border: none'>{{$lineItem->name}}</td>
                                                    <td style='border: none' class='amount right'>{{'$' . number_format($lineItem->price, 2)}}</td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td style='border: none'><b>Total: </b></td>
                                                <td class='amount right' style='border: none' width='10%'><b>{{'$' . $bill->$value}}</b></td>
                                            </tr>
                                        </table>
                                    </td>
                                @else
                                    <td>
                                        <table style='border: 0'>
                                            <tr>
                                                <td style='border: none'>{{$bill->delivery_type}}</td>
                                                <td class='amount right' style='border:none' width='10%'>{{$bill->$value}}</td>
                                            </tr>
                                        </table>
                                    </td>
                                @endif
                            @elseif($value == 'address')
                                <td class='address'>
                                    @if($bill->charge_account_id != $bill->pickup_account_id)
                                        {{$bill->pickup_address_name}}
                                    @elseif($bill->charge_account_id != $bill->delivery_account_id)
                                        {{$bill->delivery_address_name}}
                                    @endif
                                </td>
                            @elseif($value == 'bill_id')
                                <td class='bill_id' width='10%'><a href='/bills/edit/{{$bill->bill_id}}'>{{$bill->$value}}</a></td>
                            @elseif($value == 'delivery_type')
                                <td width='13%'>{{$bill->$value}}</td>
                            @elseif($value == 'time_pickup_scheduled')
                                <td width='15%'>{{substr($bill->$value, 0, 16)}}</td>
                            @else
                                <td width='10%'>{{$bill->$value}}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                @if(isset($table->subtotal))
                    <tfoot style='page-break-inside: avoid'>
                        <tr>
                            <td class='center' colspan='{{count($table->headers) - 1}}'><h3>Subtotal for {{$table_key}}</h3></td>
                            <td>
                                <table style='border: none; page-break-inside: avoid'>
                                    <tr>
                                        <td class='right'><b>Bill Subtotal:</b></td>
                                        <td class='right'><b>{{'$' . number_format($table->subtotal, 2)}}</b></td>
                                    </tr>
                                    <tr>
                                        <td class='right' style='padding-right: 3px'><b>Tax:</b></td>
                                        <td class='right'><b>{{'$' . number_format($table->tax, 2)}}</b></td>
                                    </tr>
                                    <tr>
                                        <td class='right' style='padding-right: 3px'><b>Subtotal:</b></td>
                                        <td class='right'><b>{{'$' . number_format($table->total, 2)}}</b></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </tbody>
        </table>
    <br/>
    <br/>
    @endforeach
@endif
@if(isset($model->amendments))
    <h4>Amendments</h4>
    <table>
        <thead>
            <tr>
                <td>Bill ID</td>
                <td class='right'>Adjustment Amount</td>
            </tr>
        </thead>
        <tbody>
            @foreach($model->amendments as $amendment)
                <tr>
                    <td>{{$amendment->bill_id}}</td>
                        @if($showLineItems == 'true')
                        <td>
                            <table style='border: none;'>
                                @foreach($amendment->line_items as $lineItem)
                                    <tr>
                                        <td style='border: none'>{{$lineItem->name}}</td>
                                        <td style='border: none' class='amount right'>{{'$' . number_format($lineItem->price, 2)}}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td style='border: none'>Total: </td>
                                    <td class='amount right' style='border: none' width='10%'>{{'$' . $amendment->amount}}</td>
                                </tr>
                            </table>
                        </td>
                    @else
                        <td>
                            <table style='border: 0'>
                                <tr>
                                    <td style='border: none'>{{$amendment->delivery_type}}</td>
                                    <td class='amount right' style='border:none' width='10%'>{{$amendment->amount}}</td>
                                </tr>
                            </table>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
<div style='page-break-inside: avoid'>
    @if(count($model->unpaid_invoices) > 0)
    <h4>All Invoices with Balance Owing for Account {{$model->parent->name}}</h4>
    <table class='unpaid_invoices'>
        <thead>
            <tr>
                <td>Invoice ID</td>
                <td>Date</td>
                <td class='right'>Invoice Total</td>
                <td class='right'>Balance Owing</td>
            </tr>
        </thead>
        <tbody>
            @foreach($model->unpaid_invoices as $invoice)
                <tr>
                    <td>{{$invoice->invoice_id}}</td>
                    <td>{{$invoice->bill_end_date}}</td>
                    <td class='amount right'>{{'$' . number_format($invoice->total_cost, 2)}}</td>
                    <td class='amount right'>{{'$' . number_format($invoice->balance_owing, 2)}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    <table class='totals'>
        @if($model->invoice->min_invoice_amount != null && $model->invoice->min_invoice_amount > $model->invoice->bill_cost)
            <thead>
                <tr>
                    <td colspan='2'>Minimum Billing Applied</td>
                </tr>
            </thead>
        @endif
        <tbody>
            <tr>
                <td>Bill Subtotal:</td>
                @if($model->invoice->min_invoice_amount != null && $model->invoice->min_invoice_amount > $model->invoice->bill_cost)
                    <td>{{'$' . number_format($model->invoice->min_invoice_amount, 2)}}</td>
                @else
                    <td>{{'$' . number_format($model->invoice->bill_cost, 2)}}</td>
                @endif
            </tr>
            @if($model->invoice->discount != 0)
                <tr>
                    <td>Discount:</td>
                    <td>{{'$' . number_format($model->invoice->discount, 2)}}</td>
                </tr>
            @endif
            @if($model->invoice->fuel_surcharge != 0)
                <tr>
                    <td>Discount:</td>
                    <td>{{'$' . number_format($model->invoice->discount, 2)}}</td>
                </tr>
            @endif
            <tr>
                <td>Tax:</td>
                <td>{{'$' . number_format($model->invoice->tax, 2)}}</td>
            </tr>
            <tr>
                <td>Invoice Total:</td>
                <td>{{'$' . number_format($model->invoice->total_cost, 2)}}</td>
            </tr>
        <tbody>
    </table>
</div>
