@if(isset($is_pdf))
    <link rel='stylesheet' type='text/css' href='./css/manifests/manifest_pdf_layout.css' />
@else
    <link rel='stylesheet' type='text/css' href='/css/manifests/manifest_pdf_layout.css' />
@endif

@if(isset($is_pdf))
<div class='header'>
    <table>
        <td width:'30%'>
            <h4>Driver: {{$model->driver->contact->first_name}} {{$model->driver->contact->last_name}}<br/>
                Manifest ID: {{$model->manifest->manifest_id}}<br/>
                Date: {{$model->manifest->date_run}}</h4>
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
<br/><br/>
@endif

<hr/>
<table style='overflow: visible'>
    <tbody>
        <tr>
            <td class='basic' style='width: 20%'><h4>Driver Gross:<br/><br/>${{$model->driver_total}}</h4></td>
            <td class='warn' style='width:20%'><h4>Chargebacks:<br/><br/>${{$model->chargeback_total}}</h4></td>
            <td class='basic' style='width: 20%'><h4>Driver Income:<br/><br/>${{$model->driver_income}}</h4></td>
            <td style='width: 40%; text-align: center'><a href='/employees/edit/{{$model->driver->employee_id}}' ><h2>{{$model->driver->contact->first_name}} {{$model->driver->contact->last_name}}</h2></a></td>
        </tr>
    </tbody>
</table>

<div class='center'><h3>{{$model->manifest->start_date}} to {{$model->manifest->end_date}}<br/>Summary</h3></div>
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
<div class='center'><h3>Chargebacks</h3></div>
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

<div class='col center'><h3>Detailed</h3></div>
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
