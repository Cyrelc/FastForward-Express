<style>
.addresses {
    width: 100%;
}
.addresses > tr > td {
    width: 50%;
}
body table {
    font-size: 0.9em
}
.charge-table {
    float: right;
}
.charge-table, .charge-table th, .charge-table td {
    border: 1px solid black;
    border-collapse: collapse;
    padding: 4px;
}
.delivery-details {
    width: 100%
}
.delivery-details tr th {
    text-align: left
}
.delivery-details tr td {
    text-align: right
}
.text-left {
    padding-left: 2%;
    text-align: left;
}
.text-right {
    padding-right: 2%;
    text-align: right;
}
.vertical-line {
    border-left: dashed;
    height: 95%;
    position: absolute;
    left: 50%;
    top: 10px;
    margin-left: -3px;
}
.waybill-header {
    width: 100%
}
.waybill-header * {
    padding: 0px;
    margin: 0px;
}
</style>
@for($i = 0; $i < 2; $i++)
@if($i == 0)
    <div style='width: 48%; float: left;'>
@else
    <div style='width: 48%; float: right;'>
@endif
        <div>
            <div style="width: 80%; float: left">
                <table class='waybill-header'>
                    <tr>
                        <td colspan='3' style='text-align: center'>
                            <h3 style='padding-top: 10px'>Fast Forward Express</h3>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='3' style='text-align: center'>
                            <h2>Bill # {{$model->bill->bill_id}}</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style='width: 20%'>
                            <strong>Drivers</strong>
                        </td>
                        <td>
                            <strong>Pickup: <u>{{$model->bill->pickup_driver_number}}</u></strong>
                        </td>
                        <td>
                            <strong>Delivery: <u>{{$model->bill->delivery_driver_number}}</u></strong>
                        </td>
                    </tr>
                </table>
            </div>
            <div style='width: 20%; float: right'>
                <div class='visible-print'>
                    {!! QrCode::size(85)->generate('https://fastforwardexpress.ca/app/bills/' . $model->bill->bill_id); !!}
                </div>
            </div>
        </div>
        <hr style='width: 100%; float: left'/>
        <table class='addresses'>
            <tbody>
                <tr>
                    @foreach(['Pickup Address' => 'pickup_address', 'Delivery Address' => 'delivery_address'] as $name => $address)
                        <td class='{{$address == 'pickup_address' ? 'text-left' : 'text-right' }}'>
                            <strong>{{$name}}:</strong><br/>
                            {{$model->$address->name}}<br/>
                            @foreach(explode(',', $model->$address->formatted) as $addressLine)
                                {{ltrim($addressLine)}}<br/>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
        <hr/>
        <table class='delivery-details'>
            <tr>
                <th>Delivery Type</th>
                <td>{{$model->bill->delivery_type_friendly}}</td>
            </tr>
            <tr>
                <th>Ready For Pickup</th>
                <td>{{substr($model->bill->time_pickup_scheduled, 0, -3)}}</td>
            </tr>
            <tr>
                <th>Delivery By</th>
                <td>{{substr($model->bill->time_delivery_scheduled, 0, -3)}}</td>
            </tr>
            @if(!$showCharges)
                @foreach($model->charges as $charge)
                    <tr>
                        <th>Charge To</th>
                        @if($charge->charge_account_name)
                            <td>{{$charge->charge_account_number . ' - ' . $charge->charge_account_name}}</td>
                        @else
                            <td>{{$charge->type}}</td>
                        @endif
                    </tr>
                @endforeach
            @endif
        </table>
        <table style='width: 100%'>
        </table>
        <hr/>
        @if(!$model->bill->is_min_weight_size)
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th>Package Count</th>
                        <th>Weight</th>
                        <th>Length</th>
                        <th>Width</th>
                        <th>Height</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($model->bill->packages as $package)
                        <tr>
                            <td style='text-align: center'>{{$package->count}}</td>
                            <td style='text-align: center'>{{$package->weight . ($model->bill->use_imperial ? ' lbs' : ' kgs')}}</td>
                            <td style='text-align: center'>{{$package->length . ($model->bill->use_imperial ? ' in' : ' cm')}}</td>
                            <td style='text-align: center'>{{$package->width . ($model->bill->use_imperial ? ' in' : ' cm')}}</td>
                            <td style='text-align: center'>{{$package->height . ($model->bill->use_imperial ? ' in' : ' cm')}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <hr/>
        @endif
        @if($model->bill->description)
            {{$model->bill->description}}
            <hr/>
        @endif
        <div>
            <input type='checkbox' @if($model->bill->proof_of_delivery_required == 1) checked @endif>
                Proof of Delivery Required
            </input>
        </div>
        <h5 style="width: 50%; float: left; margin-bottom: 0px">Name:</h5><h5 style="width: 50%; float: right; margin-bottom: 0px">Sign: </h5>
        <hr/>
        @if($showCharges)
            @foreach($model->charges as $charge)
                <table class='charge-table'>
                    <thead>
                        <tr>
                            @if($charge->charge_account_name)
                                <th colSpan='3'>{{$charge->account_id . ' - ' . $charge->charge_account_name}}</th>
                            @else
                                <th colSpan='3'>{{$charge->type}}</th>
                            @endif
                        </tr>
                    </thead>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($charge->lineItems as $lineItem)
                            <tr>
                                <td>{{$lineItem->name}}</td>
                                <td>{{$lineItem->friendly_type_name}}</td>
                                <td>${{$lineItem->price}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <td></td>
                            <td>${{number_format(floatval($charge->price), 2)}}</td>
                        </tr>
                    </tfoot>
                </table>
            @endforeach
        @endif
        </div>
    @if($i == 0)
        <div class="vertical-line"></div>
    @endif
    </div>
@endfor
