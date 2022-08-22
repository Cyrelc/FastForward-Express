<style>
.addresses {
    width: 100%;
}
.addresses > tr > td {
    width: 50%;
}
.charge-table {
    float: right;
}
.charge-table, .charge-table th, .charge-table td {
    border: 1px solid black;
    border-collapse: collapse;
    padding: 8px;
}
.text-left {
    padding-left: 2%;
    text-align: left;
}
.text-right {
    padding-right: 2%;
    text-align: right;
}
</style>
<table style='width: 100%'>
    <td style='width: 80%; text-align: center'>
        <h2>Bill # {{$model->bill->bill_id}}</h2>
    </td>
    <td style='width: 20%;'>
        <div class='visible-print text-center'>
            {!! QrCode::size(100)->generate('https://fastforwardexpress.ca/app/bills/' . $model->bill->bill_id); !!}
        </div>
    </td>
</table>
<hr/>
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
<table style='width: 100%'>
    <thead>
        <tr>
            <th>Delivery Type</th>
            <th>Package Ready For Pickup</th>
            <th>Delivery By</th>
        </tr>
    </thead>
    <tr>
        <td style='text-align: center'>{{$model->bill->delivery_type_friendly}}</td>
        <td style='text-align: center'>{{$model->bill->time_pickup_scheduled}}</td>
        <td style='text-align: center'>{{$model->bill->time_delivery_scheduled}}</td>
    </tr>
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
@foreach($model->charges as $charge)
    <table class='charge-table'>
        <thead>
            <tr>
                @if($charge->charge_account_name)
                    <th colSpan='3'>{{$charge->account_id . ' - ' . $charge->charge_account_name}}</th>
                @else
                    <th colSpan='3'>{{$charge->type}}
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
                    <td>{{$lineItem->type}}</td>
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

