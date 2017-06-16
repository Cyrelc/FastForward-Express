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

        dateInput('depreciate-1-start-date');
        dateInput('depreciate-2-start-date');
        dateInput('start-date');
        comboInput('parent-account-id', 'Select a Parent Account');
        comboInput('driver,select', 'Select a Driver');
        phoneInput("phone1");
        phoneInput("phone2");
        zipInput("delivery-zip");
        zipInput("billing-zip");

<!--Reconstruct all contacts-->
        @php
            if(isset($model->account->account_id)) {
                //Construct primary contact
                $c = $model->primaryContact;
                $id = $c->contact_id;
                $fName = addslashes($c->first_name);
                $lName = addslashes($c->last_name);
                $ppnId = $c->primaryPhone->phone_number_id;
                $ppn = $c->primaryPhone->phone_number;
                $ppnExt = $c->primaryPhone->extension_number;
                $emId = $c->primaryEmail->email_address_id;
                $em = $c->primaryEmail->email;

                $spnId = $spn = $spnExt = $em2Id = $em2 = null;

                if (isset($c->secondaryPhone)) {
                    $spnId = $c->secondaryPhone->phone_number_id;
                    $spn = $c->secondaryPhone->phone_number;
                    $spnExt = $c->secondaryPhone->extension_number;
                }

                if (isset($c->secondaryEmail)) {
                    $em2Id = $c->secondaryEmail->email_address_id;
                    $em2 = $c->secondaryEmail->email;
                }

                echo sprintf("
                    newTabPill(%u, '%s', '%s', %s);
                    newTabBody(%u, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s);",
                    $id, $fName, $lName, 'true', $id, $fName, $lName, $ppnId, $ppn, $ppnExt, $spnId, $spn, $spnExt, $emId, $em, $em2Id, $em2, 'true', 'false');

                foreach($model->account->contacts as $c) {
                    $id = $c->contact_id;
                    $fName = addslashes($c->first_name);
                    $lName = addslashes($c->last_name);
                    $ppnId = $c->primaryPhone->phone_number_id;
                    $ppn = $c->primaryPhone->phone_number;
                    $ppnExt = $c->primaryPhone->extension_number;
                    $emId = $c->primaryEmail->email_address_id;
                    $em = $c->primaryEmail->email;

                    $spnId = $spn = $spnExt = $em2Id = $em2 = null;

                    if (isset($c->secondaryPhone)) {
                        $spnId = $c->secondaryPhone->phone_number_id;
                        $spn = $c->secondaryPhone->phone_number;
                        $spnExt = $c->secondaryPhone->extension_number;
                    }

                    if (isset($c->secondaryEmail)) {
                        $em2Id = $c->secondaryEmail->email_address_id;
                        $em2 = $c->secondaryEmail->email;
                    }

                    echo sprintf("
                        newTabPill(%u, '%s', '%s', %s);
                        newTabBody(%u, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s);",
                        $id, $fName, $lName, 'false', $id, $fName, $lName, $ppnId, $ppn, $ppnExt, $spnId, $spn, $spnExt, $emId, $em, $em2Id, $em2, 'false', 'false');
                }
            }
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
    <input type="hidden" data-checkbox-id="give-commission-1" name="should-give-commission-1" value="{{count($model->commissions) > 0 ? "true" : "false"}}"/>
    <input type="hidden" data-checkbox-id="give-commission-2" name="should-give-commission-2" value="{{count($model->commissions) > 1 ? "true" : "false"}}"/>
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
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        <div class="row">
            <div class="panel panel-default col-lg-12">
                <div class='panel-body'>
                    <div id="parent-location" class="bottom15 col-lg-12" >
                        <select id="parent-account-id" class='form-control' name="parent-account-id">
                            <option></option>
                            @foreach ($model->accounts as $parent)
                                <option value='{{$parent->account_id}}'>{{$parent->name}}</option>
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
                        <input class='form-control' type='number' name='account-number' placeholder="Previous Account Number" value="{{$model->account->account_number}}"/>
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
                    <div class="col-lg-4 bottom15" id="commission-1-div">
                        <div class="well">
                            <h3 class="panel-title bottom15">Commission 1</h3>
                            <div class="col-lg-6 bottom15">
                                <select id="employee-1-select" class="form-control" type='text' name='commission-employee-1-id' ">
                                    <option></option>
                                    @foreach($model->drivers as $d)
                                        @if (count($model->commissions) > 0 && $d->driver_id == $model->commissions[0]->driver_id)
                                            <option selected="selected" value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                        @else
                                            <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 bottom15">
                                <div class="input-group">
                                    <input class='form-control' min=0 max=100 type='number' name='commission-1-percent' placeholder="Commission %" value="{{count($model->commissions) > 0 ? $model->commissions[0]->commission * 100 : "" }}"/>
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                            <h5>Depreciation rules</h5>
                            <hr>
                            <div class="input-group bottom15">
                                <span class="input-group-addon">Depreciate by</span>
                                <input class="form-control" min=0 max=100 type='number' name='depreciate-1-percentage' placeholder="Depreciation %" value="{{count($model->commissions) > 0 ? $model->commissions[0]->depreciation_amount * 100 : "" }}">
                                <span class="input-group-addon"> % </span>
                            </div>
                            <div class="input-group bottom15">
                                <span class="input-group-addon"> for </span>
                                <input class="form-control" min=0 max=100 type='number' name='depreciate-1-duration' placeholder="Depreciation duration" value="{{count($model->commissions) > 0 ? $model->commissions[0]->years : "" }}"/>
                                <span class="input-group-addon"> years </span>
                            </div>
                            <div class="input-group bottom15" id="driver-depreciation_start">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> starting </span>
                                <input type='text' id="depreciate-1-start-date" name="depreciate-1-start-date" class="form-control" placeholder="Depreciation start date" value="{{count($model->commissions) > 0 ? date("l, F d Y", $model->commissions[0]->start_date) : "" }}"/>
                            </div>
                        </div>
                    </div>
<!-- Commission 2 -->
                    <div class="col-lg-4 bottom15" id="commission-2-div">
                        <div class="well">
                            <h3 class="panel-title bottom15">Commission 2</h3>
                            <div class="col-lg-6 bottom15">
                                <select id="employee-2-select" class="form-control" type='text' name='commission-2-employee-id' value="{{count($model->commissions) > 1 ? $model->commissions[1]->driver_id : "" }}">
                                    <option></option>
                                    @foreach($model->drivers as $d)
                                        @if (count($model->commissions) > 1 && $d->driver_id == $model->commissions[1]->driver_id)
                                            <option selected="selected" value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                        @else
                                            <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 bottom15">
                                <div class="input-group">
                                    <input class='form-control' min=0 max=100 type='number' name='commission-2-percent' placeholder="Commission %" value="{{count($model->commissions) > 1 ? $model->commissions[1]->commission * 100 : "" }}"/>
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                            <h5>Depreciation rules</h5>
                            <hr>
                            <div class="input-group bottom15">
                                <span class="input-group-addon">Depreciate by</span>
                                <input class="form-control" min=0 max=100 type='number' name='depreciate-2-percentage' placeholder="Depreciation %" value="{{count($model->commissions) > 1 ? $model->commissions[1]->depreciation_amount * 100 : "" }}">
                                <span class="input-group-addon"> % </span>
                            </div>
                            <div class="input-group bottom15">
                                <span class="input-group-addon"> for </span>
                                <input class="form-control" min=0 max=100 type='number' name='depreciate-2-duration' placeholder="Depreciation duration" value="{{count($model->commissions) > 0 ? $model->commissions[1]->years : "" }}"/>
                                <span class="input-group-addon"> years </span>
                            </div>
                            <div class="input-group bottom15" id="depreciation_start_date_1">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> starting </span>
                                <input type='text' id="depreciate-2-start-date" name="depreciate-2-start-date" class="form-control" placeholder="Depreciation start date" value="{{count($model->commissions) > 0 ? date("l, F d Y", $model->commissions[1]->start_date) : "" }}"/>
                            </div>
                        </div>
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
                    <div class="form-group bottom15">
                        <input type="hidden" name="delivery-id" value="{{$model->deliveryAddress->address_id}}" />
                        <div class="col-lg-6">
                            <input type='text' class='form-control' name='delivery-street' placeholder="Address Line 1"  value="{{$model->deliveryAddress->street}}"/>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input type='text' id="delivery-zip" class='form-control' name='delivery-zip-postal' placeholder="Postal/Zip Code"  value="{{$model->deliveryAddress->zip_postal}}" />
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input type='text' class='form-control' name='delivery-street2' placeholder="Address Line 2" value="{{$model->deliveryAddress->street2}}" />
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input type='text' class='form-control' name='delivery-state-province' placeholder="Province/State" value="{{$model->deliveryAddress->state_province}}" />
                        </div>
                        <div class="col-lg-6">
                            <input type='text' class='form-control' name='delivery-city' placeholder="City" value="{{$model->deliveryAddress->city}}" />
                        </div>
                        <div class="col-lg-6">
                            <input type='text' class='form-control' name='delivery-country' placeholder="Country" value="{{$model->deliveryAddress->country}}" />
                        </div>
                    </div>
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
                    <div class="form-group">
                        <div class="form-group bottom15">
                            <input type="hidden" name="billing-id" value="{{isset($model->billingAddress) ? $model->billingAddress->address_id : ""}}" />
                            <div class="col-lg-6">
                                <input type='text' class='form-control' name='billing-street' placeholder="Address Line 1"  value="{{isset($model->billingAddress) ? $model->billingAddress->street : ""}}"/>
                            </div>
                            <div class="col-lg-6 bottom15">
                                <input type='text' id="billing-zip" class='form-control' name='billing-zip-postal' placeholder="Postal/Zip Code"  value="{{isset($model->billingAddress) ? $model->billingAddress->zip_postal : ""}}" />
                            </div>
                            <div class="col-lg-6 bottom15">
                                <input type='text' class='form-control' name='billing-street2' placeholder="Address Line 2" value="{{isset($model->billingAddress) ? $model->billingAddress->street2 : ""}}" />
                            </div>
                            <div class="col-lg-6 bottom15">
                                <input type='text' class='form-control' name='billing-state-province' placeholder="Province/State" value="{{isset($model->billingAddress) ? $model->billingAddress->state_province : ""}}" />
                            </div>
                            <div class="col-lg-6">
                                <input type='text' class='form-control' name='billing-city' placeholder="City" value="{{isset($model->billingAddress) ? $model->billingAddress->city : ""}}" />
                            </div>
                            <div class="col-lg-6">
                                <input type='text' class='form-control' name='billing-country' placeholder="Country" value="{{isset($model->billingAddress) ? $model->billingAddress->country : ""}}" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact Panel -->
        <div class='col-lg-12 panel panel-default' id="contacts">
            <div class='col-lg-12 panel-heading bottom15'>
                <h3 class='panel-title'>Contacts</h3>
            </div>

            <div class='col-lg-2'>
                <ul id="contact-tabs" class="tab nav nav-pills nav-stacked bottom15" role="tablist" style="list-style-type:none; padding-top:15px;">
                    <li><a href="#new-contact" aria-controls="profile" role="tab" data-toggle="tab" class="active"><i class="fa fa-plus-circle"></i> Add New</a></li>
                </ul>
            </div>
            <!-- Contact Tab panes -->
            <div class="col-lg-10">
                <div class="tab-content" id="contact-bodies">
                    <div role="tabpanel" class="tab-pane active" id="new-contact">
                        <div class="col-lg-12" style="padding:15px;">
                            <div class="clearfix form-section well" style="padding:15px;">
                                <div class="col-lg-6 bottom15">
                                    <input type='text' class='form-control contact-body' id='first-name' placeholder='First Name'/>
                                </div>
                                <div class="col-lg-6 bottom15">
                                    <input type='text' class='form-control contact-body' id='last-name' placeholder='Last Name'/>
                                </div>
                                <div class="col-lg-6 bottom15">
                                    <div class="input-group">
                                        <input type="tel" id="phone1" class='form-control contact-body' placeholder='Primary Phone'/>
                                        <span class="input-group-addon">Ext.</span>
                                        <input type="tel" id="phone1-ext" class='form-control contact-body' placeholder='Extension'/>
                                    </div>
                                </div>
                                <div class='col-lg-6 bottom15'>
                                    <div class="input-group">
                                        <input type="tel" id="phone2" class='form-control contact-body' placeholder='Secondary Phone'/>
                                        <span class="input-group-addon">Ext.</span>
                                        <input type="tel" id="phone2-ext" class='form-control contact-body' placeholder='Extension'/>
                                    </div>
                                </div>
                                <div class='col-lg-6'>
                                    <input type='email' class='form-control contact-body' id='email1' placeholder='Primary Email'/>
                                </div>
                                <div class='col-lg-6'>
                                    <input type='email' class='form-control contact-body' id='email2' placeholder='Secondary Email'/>
                                </div>
                                <div class="text-center">
                                    <ul class="nav nav-pills">
                                        <li class="text-center" title="Save">
                                            <a href="javascript:saveScContact()"><i class="fa fa-save"></i></a>
                                        </li>
                                        <li title="Delete">
                                            <a href="javascript:clearScForm()"><i class="fa fa-trash"></i></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
