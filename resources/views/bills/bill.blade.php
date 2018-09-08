@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="/js/bootstrap-combobox.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript' src='/js/bills/bill.js?{{config('view.version')}}'></script>
@parent
@endsection

@section('style')
<link rel="stylesheet" type="text/css" href="/css/bootstrap-combobox.css" />
@parent
@endsection

@section ('content')

@if(isset($model->bill->bill_id))
    <h2>Edit Bill</h2>
    @php($is_new = false)
@else
    <h2>New Bill</h2>
    @php($is_new = true)
@endif

<ul class='nav nav-tabs nav-justified'>
    <li><a data-toggle='tab' href='#basic' class='btn btn-light'><i class='fas fa-map-pin fa-2x'></i><br/>Pickup/Delivery Info</a></li>
    <li><a data-toggle='tab' href='#dispatch' class='btn btn-light'><i class='fas fa-truck fa-2x'></i><br/>Dispatch</a></li>
    <li><a data-toggle='tab' href='#payment' class='btn btn-light'><i class='far fa-credit-card fa-2x'></i><br/>Payment</a></li>
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
        <div class="col-lg-4 bottom15">
            <div class="input-group"> 
                <h4 class="input-group-addon"> Invoice Number: </h4>
                <input type="text" class="form-control" name="invoice_id" readonly value="{{$model->bill->invoice_id}}" /> 
            </div>
        </div>
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <h4 class="input-group-addon"> Manifest Number: </h4>
                <input type="text" class="form-control" name="manifest_id" readonly value="{{$model->bill->manifest_id}}" />
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
    </div>
</form>

<div class='text-center'>
    <div class='col-md-12 text-center'>
        <button type='button' class='btn btn-primary' onclick='storeBill()'><i class='far fa-save fa-2x'></i><br/>Submit</button>
    </div>
</div>
@endsection

@section ('advFilter')
<div class="well form-group">
    <form id='bill-persistence-form'>
        @if($is_new)
            <h4>On Submit</h4>
            <hr>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_date" {{Cookie::get('bill_keep_date') ? 'checked' : ''}} />Keep Date</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_charge_selection" {{Cookie::get('bill_keep_charge_selection') ? 'checked' : ''}} />Keep Charge Selection</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_charge_account" {{Cookie::get('bill_keep_charge_account') ? 'checked' : ''}} />Keep Charge Account</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_pickup_account" {{Cookie::get('bill_keep_pickup_account') ? 'checked' : '' }} />Keep Pickup Account</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_delivery_account" {{Cookie::get('bill_keep_delivery_account') ? 'checked' : '' }} />Keep Delivery Account</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_pickup_driver" {{Cookie::get('bill_keep_pickup_driver') ? 'checked' : '' }} />Keep Pickup Driver</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_delivery_driver" {{Cookie::get('bill_keep_delivery_driver') ? 'checked' : '' }} />Keep Delivery Driver</label>
            </div>
        @endif
    </form>
    <hr>
    <form id='bill_options_form'>
        <div class="checkbox">
            <label><input id="skip_invoicing" type="checkbox" name="skip_invoicing" {{$model->bill->skip_invoicing == 1 ? 'checked' : ''}} />Skip Invoicing</label>
        </div>
    </form>
</div>
@endsection
