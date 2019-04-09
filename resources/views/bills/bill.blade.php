@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="/js/bootstrap-combobox.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript' src='/js/bills/bill.js?{{config('view.version')}}'></script>
<script type='text/javascript' src='/js/partials/address.js?{{config('view.version')}}'></script>
@parent
@endsection

@section('style')
<link rel="stylesheet" type="text/css" href="/css/bootstrap-combobox.css" />
@parent
@endsection

@section ('content')
<div class='col-md-12'>
    <div class='col-md-3 bottom15'>
        @if(isset($model->bill->bill_id))
            @if($model->read_only)
                <h3>View Bill {{$model->bill->bill_id}}</h3>
                <input type='hidden' id='read_only' value='true' />
            @else
                <h3>Edit Bill {{$model->bill->bill_id}}</h3>
                <input type='hidden' id='read_only' value='false' />
            @endif
            @php($is_new = false)
        @else
            <h3>New Bill</h3>
            @php($is_new = true)
        @endif
    </div>
    <div class='col-md-3 bottom15'>
        @if(isset($model->bill->invoice_id))<h4>Invoice ID: <a href='/invoices/view/{{$model->bill->invoice_id}}'>{{$model->bill->invoice_id}}</a></h4>@endif
    </div>
    <div class='col-md-3 bottom15'>
        @if(isset($model->bill->delivery_manifest_id))<h4>Delivery Manifest ID: <a href='/manifests/view/{{$model->bill->delivery_manifest_id}}'>{{$model->bill->delivery_manifest_id}}</a></h4>@endif
    </div>
    <div class='col-md-3 bottom15'>
        @if(isset($model->bill->pickup_manifest_id))<h4>Pickup Manifest ID: <a href='/manifests/view/{{$model->bill->pickup_manfiest_id}}'>{{$model->bill->pickup_manifest_id}}</a></h4>@endif
    </div>
    @if(!$is_new && !$model->read_only)
        <div class='col-md-9 bottom15'>
            <div class='progress-bar' role='progressbar' aria-valuenow='{{$model->bill->percentage_complete * 100}}' style='width:{{$model->bill->percentage_complete * 100}}%'>{{$model->bill->percentage_complete * 100}}%</div>
        </div>
    @endif
</div>

<ul class='nav nav-tabs nav-justified'>
    <li class='active'><a data-toggle='tab' href='#basic' class='btn btn-basic'><i class='fas fa-map-pin fa-2x'></i><br/>Pickup/Delivery Info</a></li>
    <li><a data-toggle='tab' href='#dispatch' class='btn btn-basic'><i class='fas fa-truck fa-2x'></i><br/>Dispatch</a></li>
    <li><a data-toggle='tab' href='#payment' class='btn btn-basic'><i class='far fa-credit-card fa-2x'></i><br/>Payment</a></li>
    @if(isset($model->activity_log))
        <li><a data-toggle='tab' href='#activity_log' class='btn btn-basic'><i class='fas fa-book-open fa-2x'></i><br/>Activity Log</a></li>
    @endif
</ul>

<form id='bill-form'>
<!--form-->
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
<!--predetermined information -->
    <div hidden class="col-lg-12">
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <h4 class="input-group-addon"> Bill Number: </h4>
                <input type="text" class="form-control" id='bill_id' name="bill_id" readonly value="{{$model->bill->bill_id}}" style="background:0; border:0; outline:0;" />
            </div>
        </div>
    </div>

    <div class='tab-content'>
        <div id='basic' class="tab-pane fade in active well">
            @include('bills.basic')
        </div>
        <div id='dispatch' class='tab-pane fade well'>
            @include('bills.dispatch')
        </div>
        <div id='payment' class='tab-pane fade well'>
            @include('bills.payment')
        </div>
        @if(isset($model->activity_log))
            <div id='activity_log' class='tab-pane fade well'>
                @include('partials.activity_log')
            </div>
        @endif
    </div>
</form>

<div class='text-center'>
    <div class='col-md-12 text-center'>
        <button type='button' class='btn btn-primary' onclick='storeBill()' {{$model->read_only ? 'disabled hidden' : ''}}><i class='far fa-save fa-2x'></i><br/>Submit</button>
    </div>
</div>

<!-- amendment modal -->
<div id="amendment_modal" class="modal fade" role="dialog">
    <div class="modal-dialog">
<!-- amendment modal content -->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Create Amendment</h4>
            </div>
            <div class="modal-body">
                <div class='col-md-12 bottom15'>
                    <div class='input-group'>
                        <span class='input-group-addon'>Amendment Type: </span>
                        <select id='amendment_type' class='form-control selectpicker'>
                            <option value='price_adjustment'>Price Adjustment</option>
                            <option value='account_reassign'>Account Reassignment</option>
                        </select>
                    </div>
                </div>
                <div class='tab-content'>
                    <div id='price_adjustment' class="col-md-12 tab-pane fade">

                    </div>
                    <div id='account_reassign' class='col-md-12 tab-pane fade'>
                        
                    </div>
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <a id="create_amendment" type="button" class="btn btn-success" href="">Delete</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section ('advFilter')
<div class="well form-group">
    <form id='bill_options_form'>
        <div class="checkbox">
            <label><input id="skip_invoicing" type="checkbox" name="skip_invoicing" {{$model->bill->skip_invoicing == 1 ? 'checked' : ''}} />Skip Invoicing</label>
        </div>
    </form>
    <hr>
</div>
@endsection
