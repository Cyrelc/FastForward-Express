<table style='width: 100%'>
    <td style='width: 40%; text-align: center'>
        <h3>{{$model->parent->account_number}} - {{$model->parent->name}}</h3>
    </td>
    <td class='basic' >
        <h4>Bill Count:<br/>{{$model->invoice->bill_count}}</h4>
    </td>
    <td class='basic' >
        <h4>Invoice Total:<br/>{{'$' . number_format($model->invoice->total_cost, 2)}}</h4>
    </td>
    <td class='warn' >
        <h4>Balance Owing:<br/>{{'$' . number_format($model->account_owing, 2)}}</h4>
    </td>
</table>
<table class='addresses'>
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

<hr/>
@if($model->invoice->finalized === 0)
    <div id="watermark">Invoice Not Finalized</div>
@endif
@if($model->parent->invoice_comment != '')
    <p>{{$model->parent->invoice_comment}}</p>
    <hr/>
    <br/>
@endif
@if($amendmentsOnly == false)
    @foreach($model->tables as $table_key => $table)
        <table class='bill_list'>
            <thead>
                <tr>
                    @foreach($table->headers as $key => $value)
                        @if($key == 'Pickup Address')
                            @if($showPickupAndDeliveryAddress)
                                <td class='address'>Pickup Address</td>
                            @else
                                @php
                                    continue
                                @endphp
                                {{-- {{continue}} --}}
                            @endif
                        @elseif($key == 'Delivery Address')
                            @if($showPickupAndDeliveryAddress)
                                <td class='address'>Delivery Address</td>
                            @else
                                <td class='address'>Address</td>
                            @endif
                        @else
                            <td class='{{$value}}'> {{$key}} </td>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($table->bills as $bill)
                    <tr>
                        @foreach($table->headers as $key => $value)
                            @if($value == 'amount')
                                @if($showLineItems == 'true')
                                    <td style='min-width:140px'>
                                        <table style='border: none; width: 100%'>
                                            @foreach($bill->line_items as $lineItem)
                                                <tr>
                                                    <td style='border: none'>{{$lineItem->name}}</td>
                                                    <td style='border: none;' class='amount'>{{'$' . number_format($lineItem->price, 2)}}</td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td style='border: none'><b>Total: </b></td>
                                                <td class='amount' style='border: none;' width='10%'><b>{{'$' . $bill->$value}}</b></td>
                                            </tr>
                                        </table>
                                    </td>
                                @else
                                    <td style='min-width:140px'>
                                        <table style='border: none; width: 100%'>
                                            <tr>
                                                <td style='border: none'>{{$bill->delivery_type}}</td>
                                                <td class='amount' style='border:none;' width='10%'>{{$bill->$value}}</td>
                                            </tr>
                                        </table>
                                    </td>
                                @endif
                            @elseif($value == 'pickup_address_name')
                                @if($showPickupAndDeliveryAddress)
                                    <td class='address'>
                                        {{$bill->pickup_address_name}}
                                    </td>
                                @endif
                            @elseif($value == 'delivery_address_name')
                                @if($showPickupAndDeliveryAddress)
                                    <td class='address'>{{$bill->delivery_address_name}}</td>
                                @else
                                    <td class='address'>
                                        @if($bill->charge_account_id != $bill->delivery_account_id)
                                            {{$bill->delivery_address_name}}
                                        @elseif($bill->charge_account_id != $bill->pickup_account_id)
                                            {{$bill->pickup_address_name}}
                                        @endif
                                    </td>
                                @endif
                            @elseif($value == 'bill_id')
                                <td class='bill_id' style='min-width: 50px'>{{$bill->$value}}</td>
                            @elseif($value == 'delivery_type')
                                <td width='13%'>{{$bill->$value}}</td>
                            @elseif($value == 'time_pickup_scheduled')
                                <td width='14%'>{{substr($bill->$value, 0, 16)}}</td>
                            @else
                                <td>{{$bill->$value}}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                @if(isset($table->subtotal))
                    <tfoot style='page-break-inside: avoid'>
                        <tr>
                            <td class='center' colspan='{{count($table->headers) - $showPickupAndDeliveryAddress ? 1 : 2}}'><h3>Subtotal for {{$table_key}}</h3></td>
                            <td>
                                <table class='subtotal'>
                                    <tr>
                                        <td><b>Bill Subtotal:</b></td>
                                        <td class='amount'><b>{{'$' . number_format($table->subtotal, 2)}}</b></td>
                                    </tr>
                                    <tr>
                                        <td style='padding-right: 3px'><b>Tax:</b></td>
                                        <td class='amount'><b>{{'$' . number_format($table->tax, 2)}}</b></td>
                                    </tr>
                                    <tr>
                                        <td style='padding-right: 3px'><b>Subtotal:</b></td>
                                        <td class='amount'><b>{{'$' . number_format($table->total, 2)}}</b></td>
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
<div>
    @if(count($model->unpaid_invoices) > 0)
    <table class='unpaid_invoices'>
        <thead>
            <tr>
                <td colspan='4'><h4>All Invoices with Balance Owing for Account {{$model->parent->name}}</h4></td>
            </tr>
            <tr>
                <td>Invoice ID</td>
                <td>Date</td>
                <td class='text-right'>Invoice Total</td>
                <td class='text-right'>Balance Owing</td>
            </tr>
        </thead>
        <tbody>
            @foreach($model->unpaid_invoices as $invoice)
                <tr>
                    <td>{{$invoice->invoice_id}}</td>
                    <td>{{$invoice->bill_end_date}}</td>
                    <td class='amount'>{{'$' . number_format($invoice->total_cost, 2)}}</td>
                    <td class='amount'>{{'$' . number_format($invoice->balance_owing, 2)}}</td>
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
