@extends('layouts.app')

@section('script')
<script src="//cdnjs.cloudflare.com/ajax/libs/Sortable/1.6.0/Sortable.min.js"></script>
<script type='text/javascript' src='/js/dispatch/dispatch.js?{{config('view.version')}}'></script>
@parent
@endsection

@section('style')
<link rel='stylesheet' type='text/css' href='https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css' />
@parent
@endsection

@section('content')
<div class='col-md-9'>
    <div id='map' style='height: 1000px'></div>
</div>
<div class='col-md-3'>
    <div class='panel panel-default'>
        <div class='panel-heading'>
            <h3>Unassigned Bills</h3>
        </div>
        <div class='panel-body'>
            <ul class='connectedSortable' id='bill_list_new' style='padding-left:0px; min-height: 20px;'>
                @foreach($model->newBills as $bill)
                <li class='list-group-item clearfix' id='{{$bill->bill_id}}' >
                    @include('dispatch.bill_card', ['collapse' => false]);
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
            <ul class='connectedSortable' id='bill_list_{{$driver->driver_id}}' style='padding-left:0px; min-height:20px;'>
                @foreach($driver->bills_on_board as $bill)
                    <li class='list-group-item clearfix' id='{{$bill->bill_id}}'>
                        @include('dispatch.bill_card', ['collapse' => true]);
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endforeach
</div>
@endsection
