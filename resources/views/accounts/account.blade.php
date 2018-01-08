@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/validation.js'></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/account.js'></script>
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
    <input type="hidden" name="account-id" value="{{ $model->account->account_id }}" />
    <input type="hidden" data-body-id="" data-checkbox-id="sub-location" name="isSubLocation" value="{{ isset($model->parentAccount) ? "true" : "false" }}"/>
    <input type="hidden" data-checkbox-id="give-discount" name="shouldGiveDiscount" value="{{$model->account->gets_discount == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="give-commission-1" name="should-give-commission-1" value="{{$model->give_commission_1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="give-commission-2" name="should-give-commission-2" value="{{$model->give_commission_2 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="charge-interest" name="shouldChargeInterest" value="{{$model->account->charge_interest == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="gst-exempt" name="isGstExempt" value="{{$model->account->gst_exempt == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="use-custom-field" name="useCustomField" value="{{$model->account->uses_custom_field == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="can-be-parent" name="canBeParent" value="{{$model->account->can_be_parent == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-me="billing-address" data-body="billing-body" data-checkbox-id="billing-address" name="hasBillingAddress" value="{{isset($model->billingAddress->address_id) ? "true" : "false" }}"/>
    <input type="hidden" data-checkbox-id="has-fuel-surcharge" name="has-fuel-surcharge" value="{{$model->account->fuel_surcharge == 0 ? "false" : "true"}}" />
    <input type="hidden" data-checkbox-id="send-bills" name="send-bills" value="{{$model->account->send_bills == 0 ? "false" : "true"}}" />
    <input type="hidden" data-checkbox-id="send-invoices" name="send_invoices" value="{{$model->account->send_invoices == 0 ? "false" : "true"}}" />
    <div class="well">
<!--Basic Information Panel-->
        <pre id='errors' hidden='true'></pre>
        <div class="clearfix">
<!-- errors go here if submission fails -->
            @if(!empty($errors) && $errors->count() > 0)
                <br />
                <div class="col-lg-12">
                    <div class="alert alert-danger">
                        <p>The following errors occurred on submit:</p>
                        <ul>
                            @foreach($errors->all() as $message)
                                <!--Custom Messages-->
                                @if ($message === "The contacts field is required.")
                                    <li>At least one contact must be provided and marked as primary.</li>
                                @elseif ($message === "The contact- action field is required.")
                                    <li>An error has occurred. Please contact us and provide the following message: <pre>Contact Action not submitted.</pre></li>
                                @elseif($message === "The primary contact field is required.")
                                        <li>At least one contact must be selected as primary.</li>
                                @else
                                    <li>{{ $message }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Basic Info</h3>
                    </div>
                    <div class='panel-body'>
<!-- Parent Account -->
                        <div id="parent-location" class="bottom15 col-lg-12" >
                            <select id="parent-account-id" class='form-control' name="parent-account-id" data-id="-1">
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
                                <input class='form-control' id="account_number" type='text' name='account-number' placeholder="Previous Account Number" value="{{$model->account->account_number}}"/>
                                <span class="input-group-addon" id="account_number_result"></span>
                            </div>
                        </div>

<!--Account Name-->
                        <div class="col-lg-4 bottom15">
                            <input type='text' class="form-control" name="name" placeholder="Company Name" value="{{$model->account->name}}" />
                        </div>

<!--Rate Type -->
                        <div class="col-lg-4 bottom15">
                            <select class='form-control' name="rate-id" disabled >
                                <option value="-1" selected disabled>Select Rate (coming soon!)</option>
                            </select>
                        </div>

<!--Invoice Interval-->
                        <div class="col-lg-4 bottom15">
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

<!--Discount-->
                        <div class="col-lg-4 bottom15" id="discount-div">
                            <div class="input-group">
                                <input class='form-control' min=0 max=100 type='number' name='discount' placeholder="Discount %" value="{{$model->account->gets_discount == 1 ? $model->account->discount * 100 : ""}}" />
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>

<!-- Fuel Surcharge-->
                        <div class="col-lg-4 bottom15" id="fuel-surcharge">
                            <input class='form-control' min=0 max=100 type='number' name="fuel-surcharge" placeholder="Fuel surcharge %" value="{{$model->account->fuel_surcharge * 100}}" />
                        </div>

<!--Custom Field-->
                        <div class="col-lg-4 bottom15" id="custom-div">
                            <div class="input-group">
                                <input type='text' class="form-control" name='custom-tracker' placeholder="Tracking Field Name" value="{{$model->account->uses_custom_field == 1 ? $model->account->custom_field : ""}}"/>
                                <span class="input-group-addon"><input type='checkbox' name='custom-tracker-sortable' /> Sortable?</span>
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
                                <input type='checkbox' id='billing-address' name='billing-address' onclick="switchDiv(this, 'billing-div')" /> Billing Address
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
    <h4>Additional Fields</h4>
    <div class="checkbox">
        <label><input id="sub-location" type="checkbox" name="sub-location" data-div="parent-location" data-hidden-name="isSubLocation" />Is Sub-Location</label>
    </div>
    <div class="checkbox">
        <label><input id="give-discount" type="checkbox" data-div="discount-div" data-hidden-name="shouldGiveDiscount" />Give Discount</label>
    </div>
    <div class="checkbox">
        <label><input id="give-commission-1" type="checkbox" data-div="commission-1-div" data-hidden-name="should-give-commission-1" />Commission 1</label>
    </div>
    <div class="checkbox">
        <label><input id="give-commission-2" type="checkbox" data-div="commission-2-div" data-hidden-name="should-give-commission-2" />Commission 2</label>
    </div>
    <div class="checkbox">
        <label><input id="use-custom-field" type="checkbox" data-hidden-name="useCustomField" data-div="custom-div" />Use Custom Field</label>
    </div>
    <hr>
    <h4>Options</h4>
    <div class="checkbox">
        <label><input id="charge-interest" type="checkbox" data-hidden-name="shouldChargeInterest" disabled/>Charge Interest on Balance Owing</label>
    </div>
    <div class="checkbox">
        <label><input id="gst-exempt" type="checkbox" data-hidden-name="isGstExempt">Is GST Exempt</label>
    </div>
    <div class="checkbox">
        <label><input id="can-be-parent" type="checkbox" name='can-be-parent' data-hidden-name="canBeParent">Can be Parent</label>
    </div>
	<div class="checkbox">
        <label><input id="has-fuel-surcharge" type="checkbox" name="" value="" data-div="fuel-surcharge" data-hidden-name="has-fuel-surcharge">Charge Fuel Surcharge</label>
    </div>
    <div class="checkbox">
        <label><input id="send-bills" type="checkbox" name="" value="" data-hidden-name="send-bills">Send Bills</label>
    </div>
    <div class="checkbox">
        <label><input id="send-invoices" type="checkbox" name="" value="" data-hidden-name="send_invoices">Send Invoices</label>
    </div>
</div>
@endsection
