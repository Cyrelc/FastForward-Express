@if(isset($is_pdf))
    <link rel='stylesheet' type='text/css' href='./css/manifests/manifest_pdf_layout.css' />
@else
    <link rel='stylesheet' type='text/css' href='/css/manifests/manifest_pdf_layout.css' />
@endif

<hr/>
<table style='overflow: visible'>
    <tbody>
        <tr>
            <td class='basic'><h4>Driver Gross:<br/><br/>${{$model->driver_total}}</h4></td>
            <td class='warn'><h4>Chargebacks:<br/><br/>${{$model->chargeback_total}}</h4></td>
            <td class='basic'><h4>Driver Income:<br/><br/>${{$model->driver_income}}</h4></td>
            <td style='width: 60%; text-align: center'><a href='/employees/edit/{{$model->driver->employee_id}}' ><h2>{{$model->driver->contact->first_name}} {{$model->driver->contact->last_name}}</h2></a></td>
        </tr>
    </tbody>
</table>

<hr/></br>
<div class='col-md-12'><div class='center'><h3>{{$model->manifest->start_date}} to {{$model->manifest->end_date}}<br/>Summary</h3></div></div>
<table id='manifest_overview'>
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
                <td>{{$day->date}}</td>
                <td>{{$day->pickup_count}}</td>
                <td>{{$day->delivery_count}}</td>
                <td class='right'>{{$day->pickup_amount}}</td>
                <td class='right'>{{$day->delivery_amount}}</td>
                <td class='right'>{{number_format($day->pickup_amount + $day->delivery_amount, 2)}}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if($model->chargebacks != null)
<br/><br/>
<div class='col-md-12 center'><h3>Chargebacks</h3></div>
<table id='chargebacks'>
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
            <td class='right red'>{{$chargeback->amount}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<br/><br/>
<div class='col-md-12 center'><h3>Detailed</h3></div>
<table id='manifest_full'>
    <thead>
        <tr>
            <td>Bill ID</td>
            <td>Date</td>
            <td>Account</td>
            <td>Delivery Type</td>
            <td>Direction</td>
            <td class='right'>Bill Gross</td>
            <td class='right'>Driver Income</td>
        </tr>
    </thead>
    <tbody>
        @foreach($model->bills as $bill)
            <tr>
                <td><a href='/bills/view/{{$bill->bill_id}}'>{{$bill->bill_id}}</a></td>
                <td>{{$bill->date}}</td>
                <td>{{$bill->account_name}}</td>
                <td>{{$bill->delivery_type}}</td>
                <td>{{$bill->type}}</td>
                <td class='right'>{{$bill->amount}}</td>
                <td class='right'>{{$bill->driver_income}}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class='col-md-3 right' style='float:right'>
    <table>
        <tbody>
            <tr>
                <td>Driver Total Pay: </td>
                <td>{{$model->driver_total}}</td>
            </tr>
        </tbody>
    </table>
</div>
