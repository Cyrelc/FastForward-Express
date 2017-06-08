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

        dateInput('driver-depreciation_start');
        dateInput('sales-depreciation-start');
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
    <input type="hidden" data-checkbox-id="give-discount" name="shouldGiveDriverDiscount" value="{{old('shouldGiveDiscount')}}"/>
    <input type="hidden" data-checkbox-id="give-driver-commission" name="shouldGiveDriverCommission" value="{{old('shouldGiveDriverCommission')}}"/>
    <input type="hidden" data-checkbox-id="give-sales-commission" name="shouldGiveSalesCommission" value="{{old('shouldGiveSalesCommission')}}"/>
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
<!-- Driver Commission -->
                    <div class="col-lg-6 well bottom15" id="driver-commission-div">
                        <h3 class="panel-title bottom15">Driver Commission</h3>
                        <div class="col-lg-6 bottom15">
                            <select id="driver-select" class="form-control" type='text' name='driver-commission-employee-id' value="{{old('driver-commission-employee-id')}}">
                                <option></option>
                                @foreach($model->drivers as $d)
                                    <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input class='form-control' min=0 max=100 type='number' name='driver-commission-percent' placeholder="Commission %" value="{{old('driver-commission-percent')}}"/>
                        </div>
                        <div><h5>Depreciation rules</h5></div>
                        <hr>
                        <span id="depreciate" class="col-lg-12 form-group">
                            <div class="input-group bottom15">
                                <span class="input-group-addon">Depreciate by</span>
                                <input class="form-control" min=0 max=100 type='number' name='driver-depreciate-percentage' placeholder="Depreciation %" value="{{old('driver-depreciate-percentage')}}">
                                <span class="input-group-addon"> % </span>
                            </div>
                            <div class="input-group bottom15">
                                <span class="input-group-addon"> for </span>
                                <input class="form-control" min=0 max=100 type='number' name='driver-depreciate-duration' placeholder="Depreciation duration" value="{{old('driver-depreciation-duration')}}"/>
                                <span class="input-group-addon"> years </span>
                            </div>
                            <div class="input-group bottom15" id="driver-depreciation_start">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> starting </span>
                                <input id="driver-depreciation-start-date" type='text' name="driver-depreciation-start-date" class="form-control" placeholder="Depreciation start date" value="{{old('driver-depreciation-start-date')}}"/>
                            </div>
                        </span>
                    </div>
<!-- Salesman Commission -->
                    <div class="col-lg-6 well bottom15" id="sales-commission-div">
                        <h3 class="panel-title bottom15">Sales Commission</h3>
                        <div class="col-lg-6 bottom15">
                            <select id="driver-select" class="form-control" type='text' name='sales-commission-employee-id' value="{{old('sales-commission-employee-id')}}">
                                <option></option>
                                @foreach($model->drivers as $d)
                                    <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input class='form-control' min=0 max=100 type='number' name='sales-commission-percent' placeholder="Commission %" value="{{old('sales-commission-percent')}}"/>
                        </div>
                        <div><h5>Depreciation rules</h5></div>
                        <hr>
                        <span id="depreciate" class="col-lg-12 form-group">
                            <div class="input-group bottom15">
                                <span class="input-group-addon">Depreciate by</span>
                                <input class="form-control" min=0 max=100 type='number' name='sales-depreciate-percentage' placeholder="Depreciation %" value="{{old('sales-depreciate-percentage')}}">
                                <span class="input-group-addon"> % </span>
                            </div>
                            <div class="input-group bottom15">
                                <span class="input-group-addon"> for </span>
                                <input class="form-control" min=0 max=100 type='number' name='sales-depreciate-duration' placeholder="Depreciation duration" value="{{old('sales-depreciation-duration')}}"/>
                                <span class="input-group-addon"> years </span>
                            </div>
                            <div class="input-group bottom15" id="depreciation_start_date_1">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> starting </span>
                                <input id="sales-depreciation-start" type='text' name="sales-depreciation-start" class="form-control" placeholder="Depreciation start date" value="{{old('depreciation_start_date_1')}}"/>
                            </div>
                        </span>
                    </div>
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
        <label><input id="give-driver-commission" type="checkbox" value="" data-div="driver-commission-div" data-hidden-name="shouldGiveDriverCommission" />Driver Commission</label>
    </div>
    <div class="checkbox">
        <label><input id="give-sales-commission" type="checkbox" value="" data-div="sales-commission-div" data-hidden-name="shouldGiveSalesCommission" />Sales Commission</label>
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
