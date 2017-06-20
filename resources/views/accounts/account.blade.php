@extends ('layouts.app')

@section ('script')

<script type='text/javascript' src='/js/validation.js'></script>
<script type='text/javascript' src='/js/account.js'></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript'>
    $(document).ready(function() {
        //On failed validation, redisplay form correctly
        $("input[data-checkbox-id]").each(function(i,e){
            var value = $(e).val() == 'true';
            if (value) {
                var me = $(e).attr('data-me');
                var check_box_id = "#" +$(e).attr('data-checkbox-id');
                if (me) {
                    var body = $(e).attr('data-body');
                    $(check_box_id).prop('checked', true);
                    enableBody(me, body);
                } else
                    $(check_box_id).click();
            }
        });

        $("#billing-address").change(function(){
            if ($("#billing-address").prop('checked'))
                $("input[name='hasBillingAddress']").val('true');
            else
                $("input[name='hasBillingAddress']").val('');
        });

        dateInput('start-date');
        comboInput('parent-account-id', 'Select a Parent Account');
        comboInput('driver,select', 'Select a Driver');

        @php
            //Enable the billing address fields if there's a billing address
            if (isset($model->billingAddress))
                echo '$("#billing-address").prop("checked", true);
                    enableBody("billing-address", "billing-body");';
        @endphp

    });
</script>
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
<form onsubmit="saveScContact()" method="POST" action="/accounts/store">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="account-id" value="{{ $model->account->account_id }}" />
    <input type="hidden" data-body-id="" data-checkbox-id="sub-location" name="isSubLocation" value="{{ isset($model->account->account_id) ? ($model->account->is_master ? "false" : "true") : "false" }}"/>
    <input type="hidden" data-checkbox-id="give-discount" name="shouldGiveDiscount" value="{{$model->account->gets_discount == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="give-commission-1" name="should-give-commission-1" value="{{$model->give_commission_1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="give-commission-2" name="should-give-commission-2" value="{{$model->give_commission_2 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="charge-interest" name="shouldChargeInterest" value="{{$model->account->charge_interest == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="gst-exempt" name="isGstExempt" value="{{$model->account->gst_exempt == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="use-custom-field" name="useCustomField" value="{{$model->account->uses_custom_field == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="can-be-parent" name="canBeParent" value="{{$model->account->can_be_parent == 1 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="existing-account" name="hasPreviousAccount" value="{{ isset($model->account->account_number) ? "true" : "false" }}" />
    <input type="hidden" data-me="billing-address" data-body="billing-body" data-checkbox-id="billing-address" name="hasBillingAddress" value="{{isset($model->billingAddress) ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="has-invoice-comment" name="invoice-comment" value="{{strlen($model->account->invoice_comment) > 0 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="has-fuel-surcharge" name="has-fuel-surcharge" value="{{$model->account->fuel_surcharge == 0 ? "false" : "true"}}" />
    <input type="hidden" data-checkbox-id="send-bills" name="send-bills" value="{{$model->account->send_bills == 0 ? "false" : "true"}}" />
    <div class="well" style="overflow: hidden">
        <!--Basic Information Panel-->
        <pre id='errors' class='hidden'></pre>
        <div class="panel-body">
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
        <div class="row">
            <div class="panel panel-default col-lg-12">
                <div class='panel-body'>
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
                    <div class="col-lg-4 bottom15">
                        <input type='text' class="form-control" name="name" placeholder="Company Name" value="{{$model->account->name}}" />
                    </div>
                    <div class="col-lg-4 bottom15">
                        <select class='form-control' name="rate-id" disabled >
                            <option value="-1" selected disabled>Select Rate (coming soon!)</option>
                        </select>
                    </div>
                    <div class="col-lg-4 bottom15">
                        <select class='form-control' name="invoice-interval" placeholder="Select Invoice Interval">
                            @foreach ($model->invoice_intervals as $ii)
                                @if ($ii == $model->account->invoice_interval)
                                    <option selected value="{{$ii}}">{{ucfirst($ii)}}</option>
                                @else
                                    <option value="{{$ii}}">{{ucfirst($ii)}}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 bottom15" id="discount-div">
                        <div class="input-group">
                            <input class='form-control' min=0 max=100 type='number' name='discount' placeholder="Discount %" value="{{$model->account->gets_discount == 1 ? $model->account->discount * 100 : ""}}" />
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    <div class="col-lg-4 bottom15" id="fuel-surcharge">
                        <input class='form-control' min=0 max=100 type='number' name="fuel-surcharge" placeholder="Fuel surcharge %" value="{{$model->account->fuel_surcharge * 100}}" />
                    </div>
                    <div class="col-lg-4 bottom15" id="old-account">
                        <input class='form-control' type='text' name='account-number' placeholder="Previous Account Number" value="{{$model->account->account_number}}"/>
                    </div>
                    <div class="col-lg-4 bottom15" id="custom-div">
                        <div class="input-group">
                            <input type='text' class="form-control" name='custom-tracker' placeholder="Tracking Field Name" value="{{$model->account->uses_custom_field == 1 ? $model->account->custom_field : ""}}"/>
                            <span class="input-group-addon"><input type='checkbox' name='custom-tracker-sortable' /> Sortable?</span>
                        </div>
                    </div>
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
<!-- Commission 1 -->
                    <div class="col-lg-12 col-nopadding">
                    @include('partials.commission', [
                        'commission' => count($model->commissions) > 1 ? $model->commissions[0] : null,
                        'prefix' => 'commission-1',
                        'drivers' => $model->drivers,
                        'title' => 'Commission 1'
                    ])
                        <!-- Commission 2 -->
                        @include('partials.commission', [
                            'commission' => count($model->commissions) > 2 ? $model->commissions[1] : null,
                            'prefix' => 'commission-2',
                            'drivers' => $model->drivers,
                            'title' => 'Commission 2'
                        ])
                    </div>
<!-- End Commission -->
                    <div class="col-lg-12 bottom15" id="invoice-comment">
                        <label for="comment">Invoice Comment:</label>
                        <textarea class="form-control" rows="5" name="comment" placeholder="This comment will appear on every invoice sent to the account">{{$model->account->invoice_comment}}</textarea>
                    </div>
                </div>
            </div>
        </div>
<!-- Delivery address panel -->
        <div class="row row-eq-height">
            <div class="col-lg-6 panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Delivery Address</h3>
                </div>
                <div class="col-lg-12 panel-body">
                    @include('partials.address', ['prefix' => 'delivery', 'address' => $model->deliveryAddress])
                </div>
            </div>
<!-- Billing address panel -->
            <div class="col-lg-6 panel panel-default">
                <div class="col-lg-12 panel-heading">
                    <h3 class="panel-title">
                        <label style="font-weight: normal;">
                            <input type='checkbox' id='billing-address' name='billing-address' onclick="enableBody(this.id, 'billing-body')" /> Billing Address
                        </label>
                    </h3>
                </div>
                <div class="col-lg-12 panel-body">
                    @include('partials.address', ['prefix' => 'billing', 'address' => isset($model->billingAddress) ? $model->billingAddress : null])
                </div>
            </div>
        </div>
        <!-- Contact Panel -->
        @include('partials.contacts', ['contacts' => $model->account->contacts])
    </div>
    <div class='text-center'><button type='submit' class='btn btn-primary'>Submit</button></div>
</div>
</form>
@endsection

@section ('advFilter')
<div class="well form-group">
    <h3>Options</h3>
    <hr>
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
        <label><input id="has-invoice-comment" type="checkbox" value="" data-div="invoice-comment" data-hidden-name="invoice-comment" /> Invoice Comment </label>
    </div>
    <div class="checkbox">
        <label><input id="use-custom-field" type="checkbox" data-hidden-name="useCustomField" data-div="custom-div" />Use Custom Field</label>
    </div>
    <div class="checkbox">
        <label><input id="charge-interest" type="checkbox" data-hidden-name="shouldChargeInterest" />Charge Interest on Balance Owing</label>
    </div>
    <div class="checkbox">
        <label><input id="gst-exempt" type="checkbox" data-hidden-name="isGstExempt">Is GST Exempt</label>
    </div>
    <div class="checkbox">
        <label><input id="can-be-parent" type="checkbox" name='can-be-parent' data-hidden-name="canBeParent">Can be Parent</label>
    </div>
    <div class="checkbox">
        <label><input id="existing-account" type="checkbox" name="" data-div="old-account" data-hidden-name="hasPreviousAccount">Previous Account</label>
    </div>
	<div class="checkbox">
        <label><input id="has-fuel-surcharge" type="checkbox" name="" value="" data-div="fuel-surcharge" data-hidden-name="has-fuel-surcharge">Charge Fuel Surcharge</label>
    </div>
    <div class="checkbox">
        <label><input id="send-bills" type="checkbox" name="" value="" data-hidden-name="send-bills">Send Bills</label>
    </div>
</div>
@endsection
