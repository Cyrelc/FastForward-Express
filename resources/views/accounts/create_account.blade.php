@extends ('layouts.app')

@section ('script')

<script type='text/javascript' src='/js/validation.js'></script>
<script type='text/javascript' src='/js/create_account.js'></script>
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
                var cbid = "#" +$(e).attr('data-checkbox-id');
                if (me) {
                    var body = $(e).attr('data-body');
                    $(cbid).prop('checked', true);
                    enableBody(me, body);
                } else
                    $(cbid).click();
            }
        });

        $("#billing-address").change(function(){
            if ($("#billing-address").prop('checked'))
                $("input[name='hasBillingAddress']").val('true');
            else
                $("input[name='hasBillingAddress']").val('');
        });

        // dateInput('depreciation_start_date_1');
        comboInput('parent-account-id', 'Select a Parent Account');
        comboInput('driver,select', 'Select a Driver');
        phoneInput("phone1");
        phoneInput("phone2");
        zipInput("delivery-zip");
        zipInput("billing-zip");

<!--Reconstruct all contacts-->
        <?php
            use Illuminate\Support\Facades\Input;
            $oldValues = Input::old();

            foreach($oldValues as $key=>$value) {
                if (substr($key, 0, 11) == "contact-id-") {
                    $id = substr($key, 11);
                    $fName = old('contact-' . $id . '-first-name');
                    $lName = old('contact-' . $id . '-last-name');
                    $ppn = old('contact-' . $id . '-phone1');
                    $spn = old('contact-' . $id . '-phone2');
                    $em = old('contact-' . $id . '-email1');
                    $em2 = old('contact-' . $id . '-email2');

                    echo "newTabPill($id, '$fName', '$lName');\r\n";
                    echo "newTabBody($id, '$fName', '$lName', '$ppn', '$spn', '$em', '$em2');";
                }
            }
        ?>
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
<h2>New Account</h2>
<form onsubmit="saveScContact()" method="POST" action="/accounts/store">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" data-body-id="" data-checkbox-id="sub-location" name="isSubLocation" value="{{old('isSubLocation')}}"/>
    <input type="hidden" data-checkbox-id="give-discount" name="shouldGiveDiscount" value="{{old('shouldGiveDiscount')}}"/>
    <input type="hidden" data-checkbox-id="give-commission" name="shouldGiveCommission" value="{{old('shouldGiveCommission')}}"/>
    <input type="hidden" data-checkbox-id="charge-interest" name="shouldChargeInterest" value="{{old('shouldChargeInterest')}}"/>
    <input type="hidden" data-checkbox-id="gst-exempt" name="isGstExempt" value="{{old('isGstExempt')}}"/>
    <input type="hidden" data-checkbox-id="use-custom-field" name="useCustomField" value="{{old('useCustomField')}}"/>
    <input type="hidden" data-checkbox-id="can-be-parent" name="canBeParent" value="{{old('canBeParent')}}"/>
    <input type="hidden" data-checkbox-id="existing-account" name="hasPreviousAccount" value="{{old('hasPreviousAccount')}}"/>
    <input type="hidden" data-me="billing-address" data-body="billing-body" data-checkbox-id="billing-address" name="hasBillingAddress" value="{{old('hasBillingAddress')}}"/>

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
                        <input type='text' class="form-control" name="name" placeholder="Company Name" value="{{old('name')}}" />
                    </div>
                    <div class="col-lg-4 bottom15">
                        <select class='form-control' name="rate-id" disabled >
                            <option value="-1" selected disabled>Select Rate (coming soon!)</option>
                        </select>
                    </div>
                    <div class="col-lg-4 bottom15">
                        <select class='form-control' name="invoice-interval" placeholder="Select Invoice Interval" value="{{old('invoice-interval')}}">
                            <option value="weekly">Weekly</option>
                            <option value="semi-monthly">Twice a Month</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="col-lg-4 bottom15" id="discount-div">
                        <input class='form-control' min=0 max=100 type='number' name='discount' placeholder="Discount %" value="{{old('discount')}}" />
                    </div>
<!-- Commission -->
                    <div class="col-lg-6 well bottom15" id="commission-div">
<!--                         <h3 class="panel-title bottom15">Commission</h3>
                        <div class="col-lg-6 bottom15">
                            <select id="driver-select" class="form-control" type='text' name='commission-employee-id' value="{{old('commission-employee-id')}}">
                                <option></option>
                                @foreach($model->drivers as $d)
                                    <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input class='form-control' min=0 max=100 type='number' name='commission-percent' placeholder="Commission %" value="{{old('commission-percent')}}"/>
                        </div>
                        <div><h5>Depreciation rules</h5></div>
                        <hr>
                        <span id="depreciate" class="col-lg-12 form-group">
                            <span class="input-group-addon">Depreciate by</span>
                            <input class="form-control" min=0 max=100 type='number' name='depreciate_percentage' placeholder="Depreciation %" value="{{old('depreciate_percentage')}}">
                            <span class="input-group-addon"> % for </span>
                            <input class="form-control" min=0 max=100 type='number' name='depreciate_duration' placeholder="Depreciation duration" value="{{old('depreciation_duration')}}"/>
                            <span class="input-group-addon"> years </span>
                            <div class="input-group" id="depreciation_start_date_1">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                                <input id="depreciation_start_date" type='text' name="license_expiration" class="form-control" placeholder="Depreciation start date" value="{{old('depreciation_start_date_1')}}"/>
                            </div>
                        </span>
                        <div id="calculation" class="col-lg-12">
                            <!-- <p>Final comission after depreciation, as of CALCULATE DATE: CALCULATE PERCENTAGE BASED ON SETTINGS</p> -->
                        </div>
 -->                    </div>
<!-- End Commission -->
                    <div class="col-lg-4 bottom15" id="old-account">
                        <input class='form-control' type='number' name='account-num' placeholder="Previous Account Number" />
                    </div>
                    <div class="col-lg-4 bottom15" id="custom-div">
                        <div class="input-group">
                            <input type='text' class="form-control" name='custom-tracker' placeholder="Tracking Field Name" />
                            <span class="input-group-addon"><input type='checkbox' name='custom-tracker-sortable' /> Sortable?</span>
                        </div>
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
                        <div class="col-lg-6">
                            <input type='text' class='form-control' name='delivery-street' placeholder="Address Line 1"  value="{{old('delivery-street')}}"/>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input type='text' id="delivery-zip" class='form-control' name='delivery-zip-postal' placeholder="Postal/Zip Code"  value="{{old('delivery-zip-postal')}}" />
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input type='text' class='form-control' name='delivery-street2' placeholder="Address Line 2" value="{{old('delivery-street2')}}" />
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input type='text' class='form-control' name='delivery-state-province' placeholder="Province/State" value="{{old('delivery-state-province')}}" />
                        </div>
                        <div class="col-lg-6">
                            <input type='text' class='form-control' name='delivery-city' placeholder="City" value="{{old('delivery-city')}}" />
                        </div>
                        <div class="col-lg-6">
                            <input type='text' class='form-control' name='delivery-country' placeholder="Country" value="{{old('delivery-country')}}" />
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
                        <div class="col-lg-6 bottom15">
                            <input type='text' class='form-control billing-body' name='billing-street' placeholder="Address Line 1" value="{{old('billing-street')}}" disabled/>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input type='text' id="billing-zip" class='form-control billing-body' name='billing-zip-postal' placeholder="Postal/Zip Code" value="{{old('billing-zip-postal')}}" disabled/>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input type='text' class='form-control billing-body' name='billing-street2' placeholder="Address Line 2" value="{{old('billing-street2')}}" disabled/>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input type='text' class='form-control billing-body' name='billing-state-province' placeholder="Province/State" value="{{old('billing-state-province')}}" disabled/>
                        </div>
                        <div class="col-lg-6">
                            <input type='text' class='form-control billing-body' name='billing-city' placeholder="City" value="{{old('billing-city')}}" disabled/>
                        </div>
                        <div class="col-lg-6">
                            <input type='text' class='form-control billing-body' name='billing-country' placeholder="Country" value="{{old('billing-country')}}" disabled/>
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
                                    <input type="tel" id="phone1" class='form-control contact-body' id='phone1' placeholder='Primary Phone'/>
                                </div>
                                <div class='col-lg-6 bottom15'>
                                    <input class="form-control contact-body" id="phone2" id='phone2' placeholder='Secondary Phone'/>
                                </div>
                                <div class='col-lg-6'>
                                    <input type='email' class='form-control contact-body' id='email1' placeholder='Primary Email'/>
                                </div>
                                <div class='col-lg-6'>
                                    <input type='email' class='form-control contact-body' id='email2' placeholder='Secondary Email'/>
                                </div>
                                <div class="text-center">
                                    <ul class="nav nav-pills">
                                        <li class="text-center" title="save">
                                            <a href="javascript:saveScContact()"><i class="fa fa-save"></i></a>
                                        </li>
                                        <li title="delete">
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
        <label><input id="sub-location" type="checkbox" value="" name="sub-location" data-div="parent-location" data-hidden-name="isSubLocation" />Is Sub-Location</label>
    </div>
    <div class="checkbox">
        <label><input id="give-discount" type="checkbox" value="" data-div="discount-div" data-hidden-name="shouldGiveDiscount" />Give Discount</label>
    </div>
    <div class="checkbox">
        <label><input id="give-commission" type="checkbox" value="" data-div="commission-div" data-hidden-name="shouldGiveCommission" />Give Commission</label>
    </div>
    <div class="checkbox">
        <label><input id="use-custom-field" type="checkbox" value="" data-hidden-name="useCustomField" data-div="custom-div" />Use Custom Field</label>
    </div>
    <div class="checkbox">
        <label><input id="charge-interest" type="checkbox" value="" data-hidden-name="shouldChargeInterest" />Charge Interest on Balance Owing</label>
    </div>
    <div class="checkbox">
        <label><input id="gst-exempt" type="checkbox" value="" data-hidden-name="isGstExempt">Is GST Exempt</label>
    </div>
    <div class="checkbox">
        <label><input id="can-be-parent" type="checkbox" name='can-be-parent' value="" data-hidden-name="canBeParent">Can be Parent</label>
    </div>
    <div class="checkbox">
        <label><input id="existing-account" type="checkbox" name="" value="" data-div="old-account" data-hidden-name="hasPreviousAccount">Previous Account</label>
    </div>
</div>
@endsection
