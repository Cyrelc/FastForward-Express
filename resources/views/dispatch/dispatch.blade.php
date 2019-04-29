@extends('layouts.app')

@section('script')
<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script type='text/javascript' src='/js/dispatch/dispatch.js?{{config('view.version')}}'></script>
@parent
@endsection

@section('style')
<link rel='stylesheet' type='text/css' href='https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css' />
@parent
@endsection

@section('content')
<div class='col-md-9'>
    <h4>Placeholder Map (demo purposes only). NOTE: this version of the dispatch screen is for demo purposes only. No interactions made here actually change anything atm.</h4>
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d75843.24366772827!2d-113.53661857299727!3d53.55595796342196!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x53a02666b9121c57%3A0x74f526be9681cc9e!2sFast+Forward+Express+Ltd!5e0!3m2!1sen!2sca!4v1556562294492!5m2!1sen!2sca" width="100%" height="1000" frameborder="0" style="border:0" allowfullscreen></iframe>
</div>
<div class='col-md-3'>
    <div class='panel panel-default'>
        <div class='panel-heading'>
            <h3>Unassigned Bills</h3>
        </div>
        <div class='panel-body'>
            <ul class='connectedSortable' id='bill_list_new' style='padding-left:0px'>
                @foreach($model->newBills as $bill)
                <li class='list-group-item clearfix' id='{{$bill->bill_id}}' >
                    <i class='fas fa-arrows-alt handle'></i>
                    Bill: <a href='/bills/edit/{{$bill->bill_id}}' target='none'>{{$bill->bill_id}}</a>&emsp;{{$bill->delivery_type}}
                    <button type='button' class='btn btn-xs btn-default' data-toggle='collapse' data-target='#details_{{$bill->bill_id}}' style='float:right'><i class='fas fa-chevron-down'></i></button>
                    <div class='col-md-12 collapse in' id='details_{{$bill->bill_id}}'>
                        <h4>Pickup</h4>
                        <pre><strong>Location: </strong>{{$bill->pickup_address_name}}<br/><strong>Scheduled: </strong>{{$bill->time_pickup_scheduled}}<br/><strong>Actual: </strong>{{$bill->time_picked_up}}</pre>
                        <hr/>
                        <h4>Delivery</h4>
                        <pre><label>Location: </label>{{$bill->delivery_address_name}}<br/><label>Scheduled: </label>{{$bill->time_delivery_scheduled}}<br/><label>Actual: </label>{{$bill->time_delivered}}</pre>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection

@section('advFilter')
<div class='well'>
@foreach($model->drivers as $driver)
    <div class='panel panel-default'>
        <div class='panel-heading'>
            {{$driver->employee_name}}
            <button type='button' class='btn btn-default btn-xs' data-toggle='collapse' data-target='#driver_{{$driver->employee_id}}' style='float:right'><i class='fas fa-chevron-down'></i></button>
        </div>
        <div class='panel-body collapse in' id='driver_{{$driver->employee_id}}'>
            <ul class='connectedSortable' id='bill_list_{{$driver->driver_id}}' style='padding-left:0px'>
                @foreach($driver->bills_on_board as $bill)
                    <li class='list-group-item clearfix' id='{{$bill->bill_id}}'>
                        <i class='fas fa-arrows-alt handle'></i>
                        Bill: <a href='/bills/edit/{{$bill->bill_id}}' target='none'>{{$bill->bill_id}}</a>&emsp;{{$bill->delivery_type}}
                        <button type='button' class='btn btn-xs btn-default' data-toggle='collapse' data-target='#details_{{$bill->bill_id}}' style='float:right'><i class='fas fa-chevron-down'></i></button>
                        <div class='col-md-12 collapse' id='details_{{$bill->bill_id}}'>
                            <h4>Pickup</h4>
                            <label>Location: </label>{{$bill->pickup_address_name}}<br/>
                            <label>Scheduled: </label>{{$bill->time_pickup_scheduled}}<br/>
                            <label>Actual: <label>{{$bill->time_picked_up}}<br/>
                            <hr/>
                            <h4>Delivery</h4>
                            <label>Location: </label>{{$bill->delivery_address_name}}<br/>
                            <label>Scheduled: </label>{{$bill->time_delivery_scheduled}}<br/>
                            <label>Actual: </label>{{$bill->time_delivered}}<br/>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endforeach
</div>
@endsection
