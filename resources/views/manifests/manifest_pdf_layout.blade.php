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
        @foreach($model->lines as $line)
            <tr>
                <td><a href='/bills/view/{{$line->bill_id}}'>{{$line->bill_id}}</a></td>
                <td>{{$line->date}}</td>
                <td>{{$line->account_name}}</td>
                <td>{{$line->delivery_type}}</td>
                <td>{{$line->type}}</td>
                <td class='right'>{{$line->bill_amount}}</td>
                <td class='right'>{{$line->driver_amount}}</td>
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
