@if(isset($is_pdf))
    <link rel='stylesheet' type='text/css' href='./css/invoices/invoice_table.css' />
@else
    <link rel='stylesheet' type='text/css' href='/css/invoices/invoice_table.css' />
@endif

<hr/>
<table style='overflow: visible'>
    <td style='width: 60%; text-align: center'>
        <h2><a href='/accounts/edit/{{$model->parent->account_id}}'>{{$model->parent->name}}</a></h2>
    </td>
    <td class='basic'>
        <h4>Invoice Total:<br/><br/>{{$model->invoice->total_cost}}</h4>
    </td>
    <td class='warn'>
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
@foreach($model->tables as $table)
    @if(count($model->tables) > 1)
        <h4>Sub Location: <a href='/accounts/edit/{{$table->charge_account_id}}'>{{$table->charge_account_name}}</a></h4>
    @endif
    <table class='bill_list'>
        <thead>
            <tr>
                @foreach($model->headers as $key => $value)
                    <td class='{{$value}}'> {{$key}} </td>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($table->lines as $row)
                @if($row->is_subtotal)
                    <tr class='subtotal'>
                @else
                    <tr>
                @endif
                @foreach($model->headers as $key => $value)
                    <td class='{{$value}}'> {{$row->$value}} </td>
                @endforeach
                </tr>
            @endforeach
            @if(count($model->tables) > 1)
                <tr class='subtotal'> 
                    @for($i = 0; $i < count($model->headers) - 2; $i++)
                        <td></td>
                    @endfor
                    <td class='right'>Subtotal</td>
                    <td class='right'>{{$table->bill_subtotal}}</td>
                </tr>
            @endif
        </tbody>
    </table>
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
