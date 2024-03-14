<link rel="stylesheet" type="text/css" href={{public_path("css/manifest_pdf.css")}}>
<table style='overflow: visible'>
    <tbody>
        <tr>
            <td class='basic'><h4>Driver Gross:<br/>${{$model->driver_total}}</h4></td>
            <td class='warn'><h4>Chargebacks:<br/>${{$model->chargeback_total}}</h4></td>
            <td class='basic'><h4>Driver Income:<br/>${{$model->driver_income}}</h4></td>
            <td style='width: 40%; text-align: center'><h2>{{$model->employee->contact->first_name}} {{$model->employee->contact->last_name}}</h2></td>
        </tr>
    </tbody>
</table>
<table id='address'>
    <tr>
        <td class='text-left'>
            <strong>Address:</strong><br/>
            @foreach(explode(',', $model->employee->contact->address->formatted) as $addressLine)
                {{$addressLine}}<br/>
            @endforeach
        </td>
    </tr>
</table>
@if($model->employee->warnings != [])
<div style='text-align: center'>
    <table style='width: 100%'>
        <thead>
            <tr>
                @foreach($model->employee->warnings as $warning)
                    @if($warning['type'] === 'error')
                        <th style='background: tomato; border: 2px solid black;'>{{$warning['friendlyString']}}</td>
                    @else
                        <th style='background: gold; border: 2px solid black;'>{{$warning['friendlyString']}}</td>
                    @endif
                @endforeach
            </tr>
        </thead>
    </table>
    <h4>Please contact the office with your up-to-date information as soon as possible</h4>
</div>
@endif
<div class='center'><h3>{{$model->manifest->start_date}} to {{$model->manifest->end_date}}<br/>Driver Statement</h3></div>
<table id='manifest_overview' @if($model->chargebacks == null) style='page-break-after: always;' @endif>
    <thead>
        <tr>
            <td>Date</td>
            <td>Pickups</td>
            <td>Deliveries</td>
            <td class='right'>Pickup Income</td>
            <td class='right'>Delivery Income</td>
            <td class='right'>Driver Income</td>
        </tr>
    </thead>
    <tbody>
        @foreach($model->overview as $day)
            <tr>
                <td>{{$day->time_pickup_scheduled}}</td>
                <td>{{$day->pickup_count}}</td>
                <td>{{$day->delivery_count}}</td>
                <td class='right'>{{'$' . number_format($day->pickup_amount, 2)}}</td>
                <td class='right'>{{'$' . number_format($day->delivery_amount, 2)}}</td>
                <td class='right'>{{'$' . number_format($day->pickup_amount + $day->delivery_amount, 2)}}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if($model->chargebacks != null)
<div class='center'><h3>Chargebacks</h3></div>
<table id='chargebacks' style='page-break-after: always;'>
    <thead>
        <tr>
            <td>Name</td>
            <td>GL Code</td>
            <td>Description</td>
            <td class='right'>Amount</td>
        </tr>
    </thead>
    <tbody>
        @foreach($model->chargebacks as $chargeback)
        <tr>
            <td>{{$chargeback->name}}</td>
            <td>{{$chargeback->gl_code}}</td>
            <td>{{$chargeback->description}}</td>
            <td class='right red'>{{'$' . number_format($chargeback->amount, 2)}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if(!$withoutBills)
<div class='col center'><h3>Detailed</h3></div>
<table id='manifest_full'>
    <thead>
        <tr>
            <td>Bill ID</td>
            <td>Waybill Number</td>
            <td>Date</td>
            <td>Delivery Type</td>
            <td>Direction</td>
            <td class='right'>Bill Gross</td>
            <td class='right'>Driver Income</td>
        </tr>
    </thead>
    <tbody>
        <?php $temp = null; ?>
        @foreach($model->bills as $bill)
            @if($temp != $bill->day)
                <?php $temp = $bill->day; ?>
                <tr>
                    <td colspan='7' style='text-align:center; background-color: gainsboro;'>{{$temp}}</td>
                </tr>
            @endif
            <tr>
                <td>{{$bill->bill_id}}</td>
                <td>{{$bill->bill_number}}</td>
                <td>{{$bill->time_pickup_scheduled}}</td>
                <td>{{$bill->delivery_type}}</td>
                <td>{{$bill->type}}</td>
                <td class='right'>{{'$' . number_format($bill->amount, 2)}}</td>
                <td class='right'>{{'$' . number_format($bill->driver_income, 2)}}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
