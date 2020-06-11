@if(isset($is_pdf))
    <link rel='stylesheet' type='text/css' href='./css/invoices/invoice_table_pdf.css' />
@else
    <link rel='stylesheet' type='text/css' href='/css/invoices/invoice_table.css' />
@endif

<hr/>
<table style='overflow: visible'>
    <td style='width: 40%; text-align: center'>
        <h3><a href='/accounts/edit/{{$model->parent->account_id}}'>{{$model->parent->name}}</a></h3>
    </td>
    <td class='basic' >
        <h4>Bill Count:<br/><br/>{{$model->invoice->bill_count}}</h4>
    </td>
    <td class='basic' >
        <h4>Invoice Total:<br/><br/>{{$model->invoice->total_cost}}</h4>
    </td>
    <td class='warn' >
        <h4>Account Balance:<br/><br/>{{$model->account_owing}}</h4>
    </td>
</table>

@if(isset($is_pdf))
<div class='header'>
    <table>
        <td width:'30%'>
            <h4>Account Number: {{$model->parent->account_number}}<br/>
                Invoice Number: {{$model->invoice->invoice_id}}<br/>
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
        <td style='width: 30%'>(780) 458-1074</td>
        <td style='width: 40%; text-align: center;'>Page: <span class='pagenum'></span></td>
        <td style='width: 30%; text-align: right'><a href='www.fastforwardexpress.com'>www.fastforwardexpress.com</a></td>
    </table>
</div>
<table id='addresses'>
    <tr>
        @foreach(['Billing Address' => 'billing_address', 'Shipping Address' => 'shipping_address'] as $name => $address)
        <td class='{{$address == "billing_address" ? 'text-left' : 'text-right' }}'>
            <strong>{{$name}}:</strong><br/>
            {{$model->parent->$address->name}}<br/>
            {{$model->parent->$address->street}}<br/>
            @if($model->parent->$address->street2 != '')
                {{$model->parent->$address->street2}}<br/>
            @endif
            {{$model->parent->$address->city}}, {{$model->parent->$address->state_province}}, {{$model->parent->$address->country}}<br/>
            {{$model->parent->$address->zip_postal}}
        </td>
        @endforeach
    </tr>
</table>
<br/><br/>
@endif

<hr/><p>{{$model->parent->invoice_comment}}</p><hr/>
</br>
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
                            <td class='amount'>{{$bill->$value}}</td>
                        @elseif($value == 'address')
                            <td class='address'>
                                @if($bill->charge_account_id != $bill->pickup_account_id)
                                    {{$bill->pickup_address_name}}
                                @elseif($bill->charge_account_id != $bill->delivery_account_id)
                                    {{$bill->delivery_address_name}}
                                @endif
                            </td>
                        @else
                            <td>{{$bill->$value}}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
            @if(count($model->tables) > 1)
                <tr class='subtotal'>
                    <td class='center' colspan='{{count($table->headers) - 2}}'>Subtotal for {{$table_key}}</td>
                    <td class='right'>Bill Subtotal:</td>
                    <td class='right'>{{$table->subtotal}}</td>
                </tr>
                <tr class='subtotal'>
                    <td colspan='{{count($table->headers) - 2}}'></td>
                    <td class='right'>Tax:</td>
                    <td class='right'>{{$table->tax}}</td>
                </tr>
                <tr class='subtotal'>
                    <td colspan='{{count($table->headers) - 2}}'></td>
                    <td class='right'>Subtotal:</td>
                    <td class='right'>{{$table->total}}</td>
                </tr>
            @endif
        </tbody>
    </table>
<br/>
<br/>
@endforeach
@if(count($model->unpaid_invoices) > 0)
<h4>All Invoices with Balance Owing for Account {{$model->parent->name}}</h4>
<table class='unpaid_invoices'>
    <thead>
        <tr>
            <td>Invoice ID</td>
            <td>Date</td>
            <td>Invoice Total</td>
            <td>Balance Owing</td>
        </tr>
    </thead>
    <tbody>
        @foreach($model->unpaid_invoices as $invoice)
            <tr>
                <td><a href='/invoices/view/{{$invoice->invoice_id}}'>{{$invoice->invoice_id}}</a></td>
                <td>{{$invoice->bill_end_date}}</td>
                <td>{{$invoice->total_cost}}</td>
                <td>{{$invoice->balance_owing}}</td>
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
                <td>{{$model->invoice->min_invoice_amount}}</td>
            @else
                <td>{{$model->invoice->bill_cost}}</td>
            @endif
        </tr>
        @if($model->invoice->discount != 0)
            <tr>
                <td>Discount:</td>
                <td>{{$model->invoice->discount}}</td>
            </tr>
        @endif
        @if($model->invoice->fuel_surcharge != 0)
            <tr>
                <td>Discount:</td>
                <td>{{$model->invoice->discount}}</td>
            </tr>
        @endif
        <tr>
            <td>Tax:</td>
            <td>{{$model->invoice->tax}}</td>
        </tr>
        <tr>
            <td>Invoice Total:</td>
            <td>{{$model->invoice->total_cost}}</td>
        </tr>
    <tbody>
</table>
