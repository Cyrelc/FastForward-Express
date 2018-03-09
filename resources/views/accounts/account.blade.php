@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type="text/javascript" src='/js/toastr.min.js'> </script>
<script type='text/javascript' src='{{URL::to('/')}}/js/validation.js'></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/accounts/account.js'></script>
{{--  <script type='text/javascript'>
    $(document).ready(function() {
        //On failed validation, redisplay form correctly
        @php
            //Enable the billing address fields if there's a billing address
            if (isset($model->billingAddress) && isset($model->billingAddress->address_id))
                echo '$("#billing-address").prop("checked", true);
                    enableBody("billing-address", "billing-body");';
        @endphp
    });
</script>  --}}
@parent

@endsection

@section ('style')
<link rel="stylesheet" type="text/css" href="{{URL::to('/')}}/css/bootstrap-combobox.css" />
<link rel='stylesheet' type='text/css' href='/css/toastr.min.css' />
<style type="text/css">
#errors {
    color: red;
}

.split-50 {
    width: 50%;
    float: left;
}
</style>

@endsection

@section ('content')
    @if (isset($model->account->account_id))
        <h2>Edit Account</h2>
    @else
        <h2>New Account</h2>
    @endif
<form id='account_form'>
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" id='account-id' name="account-id" value="{{ $model->account->account_id }}" />
    <div class="well">
<!--Basic Information Panel-->
        <div class="clearfix">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Basic Info</h3>
                    </div>
                    <div class='panel-body'>
<!-- Parent Account -->
                        <div id="parent-location" class="bottom15 col-lg-12" >
                            <select id="parent-account-id" class='form-control' name="parent-account-id">
                                <option></option>
                                @foreach ($model->accounts as $parent)
                                    @if (isset($model->account->account_id) && $model->account->parent_account_id == $parent->account_id)
                                        <option selected value='{{$parent->account_id}}'>{{$parent->name}}</option>
                                    @else
                                        <option value='{{$parent->account_id}}'>{{$parent->name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
<!--Account Number-->
                        <div class="col-lg-4 bottom15">
                            <div class="input-group">
                                <span class='input-group-addon'>Account Number</span>
                                <input class='form-control' id="account_number" type='text' name='account-number' placeholder="Previous Account Number" value="{{$model->account->account_number}}"/>
                                <span class="input-group-addon" id="account_number_result"></span>
                            </div>
                        </div>
<!--Account Name-->
                        <div class="col-lg-4 bottom15">
                            <div class='input-group'>
                                <span class='input-group-addon'>Name</span>
                                <input type='text' class="form-control" id='name' name="name" placeholder="Company Name" value="{{$model->account->name}}" />
                            </div>
                        </div>
<!--Rate Type -->
                        <div class="col-lg-4 bottom15">
                            <select class='form-control' name="rate-id" disabled >
                                <option value="-1" selected disabled>Select Rate (coming soon!)</option>
                            </select>
                        </div>
<!--Invoice Interval-->
                        <div class="col-lg-4 bottom15">
                            <div class='input-group'>
                                <span class='input-group-addon'>Invoice Interval</span>
                                <select class='form-control' name="invoice-interval" placeholder="Select Invoice Interval">
                                    @foreach ($model->invoice_intervals as $ii)
                                        @if (isset($model->account->invoice_interval) && $ii->value ==$model->account->invoice_interval)
                                            <option selected value="{{$ii->value}}">{{$ii->name}}</option>
                                        @else
                                            <option value="{{$ii->value}}">{{$ii->name}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
<!--Discount-->
                        <div class="col-lg-4 bottom15" id="discount-div">
                            <div class="input-group">
                                <span class='input-group-addon'>Discount</span>
                                <input class='form-control' min=0 max=100 step='0.01' type='number' name='discount' placeholder="Discount %" value="{{$model->account->has_discount == 1 ? $model->account->discount : ""}}" />
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>
<!--Minimum Invoice Amount-->
                        <div class='col-lg-4 bottom15' id='min_invoice_amount_div'>
                            <span class='input-group'>
                                <span class='input-group-addon'>Minimum Invoice Payment $</span>
                                <input class='form-control' min=0 max=100 step='0.01' type='number' name='min_invoice_amount' placeholder='Minimum Payment' value='{{$model->account->min_invoice_amount}}' />
                            </span>
                        </div>
<!-- Fuel Surcharge-->
                        <div class="col-lg-4 bottom15" id="fuel-surcharge">
                            <div class='input-group'>
                                <span class='input-group-addon'>Fuel Surcharge</span>
                                <input class='form-control' min=0 max=100 step='0.01' type='number' name="fuel-surcharge" placeholder="Fuel surcharge %" value="{{$model->account->fuel_surcharge}}" />
                                <span class='input-group-addon'>%</span>
                            </div>
                        </div>
<!--Custom Field-->
                        <div class="col-lg-4 bottom15" id="custom-div">
                            <div class="input-group">
                                <input type='text' class="form-control" name='custom-tracker' placeholder="Tracking Field Name" value="{{$model->account->uses_custom_field == 1 ? $model->account->custom_field : ""}}"/>
                            </div>
                        </div>
<!--Start Date-->
                        <div class="col-lg-4 bottom15">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    Start Date
                                </span>
                                <input type='text' id="start-date" class="form-control" name='start-date' placeholder="Start Date" value="{{date("l, F d Y", $model->account->start_date)}}"/>
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                            </div>
                        </div>
<!-- Commissions -->
                        <div class="col-lg-12 col-nopadding">
                            @include('partials.commission', [
                                'commission' => count($model->commissions) > 0 ? $model->commissions[0] : null,
                                'prefix' => 'commission-1',
                                'employees' => $model->employees,
                                'title' => 'Commission 1'
                            ])

                            @include('partials.commission', [
                                'commission' => count($model->commissions) > 1 ? $model->commissions[1] : null,
                                'prefix' => 'commission-2',
                                'employees' => $model->employees,
                                'title' => 'Commission 2'
                            ])
                        </div>
                    </div>
                </div>
            </div>
<!-- Addresses -->
            <div class="col-lg-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Delivery Address</h3>
                    </div>
                    <div class="panel-body">
                        @include('partials.address', ['prefix' => 'delivery', 'address' => $model->deliveryAddress, 'enabled' => true])
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="panel panel-default">
                    <div class="panel-heading" style="height: 39px;">
                        <h3 class="panel-title">
                            <label style="font-weight: normal;">
                                <input type='checkbox' id='billing-address' name='billing-address' {{isset($model->billingAddress->address_id) ? 'checked' : ''}} onclick='switchDiv(this, "billing-div")'/> Billing Address
                            </label>
                        </h3>
                    </div>
                    <div class="panel-body">
                        @include('partials.address', ['prefix' => 'billing', 'address' => $model->billingAddress, 'enabled' => isset($model->billingAddress->address_id)])
                    </div>
                </div>
            </div>
<!-- Contacts Panel -->
            @include('partials.contacts', ['contacts' => $model->account->contacts, 'show_address' => false, 'prefix' => 'account', 'title' => 'Contacts'])
        </div>
        <div class='text-center'><button type='button' class='btn btn-primary' onclick='storeAccount()'>Submit</button></div>
    </div>
</form>
@endsection

@section ('advFilter')
<div class="well form-group">
    <form id='account_options'>
        <h4>Additional Fields</h4>
        <div class="checkbox">
            <label><input id="isSubLocation" type="checkbox" name="isSubLocation" data-div="parent-location" {{$model->account->has_parent == 1 ? 'checked' : ''}}/>Is Sub-Location</label>
        </div>
        <div class="checkbox">
            <label><input id="giveDiscount" type="checkbox" name='giveDiscount' data-div="discount-div" {{$model->account->has_discount == 1 ? 'checked' : ''}} />Give Discount</label>
        </div>
        <div class="checkbox">
            <label><input id="giveCommission1" type="checkbox" name='giveCommission1' data-div="commission-1-div" disabled {{$model->give_commission_1 ? 'checked' : ''}}/>Commission 1</label>
        </div>
        <div class="checkbox">
            <label><input id="giveCommission2" type="checkbox" name='giveCommission2' data-div="commission-2-div" disabled {{$model->give_commission_2 ? 'checked' : ''}}/>Commission 2</label>
        </div>
        <div class="checkbox">
            <label><input id="useCustomField" type="checkbox" name="useCustomField" data-div="custom-div" {{$model->account->uses_custom_field == 1 ? 'checked' : ''}} />Use Custom Field</label>
        </div>
        <div class="checkbox">
            <label><input type="checkbox" name="hasFuelSurcharge" data-div="fuel-surcharge" {{$model->account->fuel_surcharge == 0 ? '' : 'checked'}}>Charge Fuel Surcharge</label>
        </div>
        <div class='checkbox'>
            <label><input type='checkbox' id='has_min_invoice_amount' name='has_min_invoice_amount' data-div='min_invoice_amount_div' {{$model->account->min_invoice_amount == 0 ? '' : 'checked'}} />Minimum Invoice Payment</label>
        </div>
        <hr>
        <h4>Options</h4>
        <div class="checkbox">
            <label><input id="chargeInterest" type="checkbox" name="chargeInterest" disabled {{$model->account->charge_interest == 1 ? 'checked' : ''}}/>Charge Interest on Balance Owing</label>
        </div>
        <div class="checkbox">
            <label><input type="checkbox" name='isGstExempt' {{$model->account->gst_exempt == 1 ? 'checked' : ''}}>Is GST Exempt</label>
        </div>
        <div class="checkbox">
            <label><input type="checkbox" name='canBeParent' {{$model->account->can_be_parent == 1 ? 'checked' : ''}}>Can be Parent</label>
        </div>
        <div class="checkbox">
            <label><input type="checkbox" name="send_bills" {{$model->account->send_bills == 1 ? 'checked' : ''}}>Send Bills</label>
        </div>
        <div class="checkbox">
            <label><input type="checkbox" name="send_invoices" {{$model->account->send_invoices == 1 ? 'checked' : ''}}>Send Invoices</label>
        </div>
    </form>
    @if(isset($model->account->account_id))
        <hr>
        <h4>Navigation<h4>
        <a class='btn btn-info' href='/invoices/layouts/{{$model->account->account_id}}'>Go To Invoice Layout</a>
    @endif
</div>
@endsection
