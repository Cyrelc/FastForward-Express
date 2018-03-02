@if(isset($is_pdf))
    <link rel='stylesheet' type='text/css' href='./css/invoices/invoice_table.css' />
@else
    <link rel='stylesheet' type='text/css' href='/css/invoices/invoice_table.css' />
@endif

<hr/>
<table style='overflow: visible'>
    <td style='width: 40%; text-align: center'>
        <h2><a href='/accounts/edit/{{$model->parent->account_id}}'>{{$model->parent->name}}</a></h2>
    </td>
    <td class='basic' >
        <h4>Bill Count:<br/><br/>{{$model->invoice->bill_count}}</h4>
    </td>
    <td class='basic' >
        <h4>Invoice Total:<br/><br/>{{$model->invoice->total_cost}}</h4>
    </td>
    <td class='warn' >
        <h4>Account Balance:<br/><br/>{{$model->invoice->total_cost}}</h4>
    </td>
</table>

@if(isset($is_pdf))
<div class='header'>
    <table>
        <td width:'30%'>
            <h4>Account Number: {{$model->parent->account_number}}<br/>
                Invoice Number: {{$model->invoice->invoice_id}}<br/>
                Date: {{$model->invoice->date}}</h4>
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
                        @else
                            <td>{{$bill->$value}}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    @if(count($model->tables) > 1)
<br/>
<br/>
    <div class='center'>
        <h4>Subtotal For {{$table_key}}</h4>
        <table class='subtotal center'>
            <tbody>
                <tr>
                    <td class='amount'>Bill Cost:</td>
                    <td class='amount'>{{$table->subtotal}}</td>
                </tr>
                <tr>
                    <td class='amount'>Tax:</td>
                    <td class='amount'>{{$table->tax}}</td>
                </tr>
                <tr>
                    <td class='amount'>Total:</td>
                    <td class='amount'>{{$table->total}}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
<br/>
<br/>
@endforeach
<table class='totals'>
    <tbody>
        <tr>
            <td>Bill Subtotal:</td>
            <td>{{$model->invoice->bill_cost}}</td>
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
