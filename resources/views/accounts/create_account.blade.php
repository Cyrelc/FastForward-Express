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
    <input type="hidden" data-checkbox-id="sub-location" name="isSubLocation" value="{{old('isSubLocation')}}"/>
    <input type="hidden" data-checkbox-id="give-discount" name="shouldGiveDriverDiscount" value="{{old('shouldGiveDiscount')}}"/>
    <input type="hidden" data-checkbox-id="give-commission-1" name="shouldGiveDriverCommission" value="{{old('shouldGiveDriverCommission')}}"/>
    <input type="hidden" data-checkbox-id="give-commission-2" name="shouldGiveSalesCommission" value="{{old('shouldGiveSalesCommission')}}"/>
    <input type="hidden" data-checkbox-id="charge-interest" name="shouldChargeInterest" value="{{old('shouldChargeInterest')}}"/>
    <input type="hidden" data-checkbox-id="gst-exempt" name="isGstExempt" value="{{old('isGstExempt')}}"/>
    <input type="hidden" data-checkbox-id="use-custom-field" name="useCustomField" value="{{old('useCustomField')}}"/>
    <input type="hidden" data-checkbox-id="can-be-parent" name="canBeParent" value="{{old('canBeParent')}}"/>
    <input type="hidden" data-checkbox-id="existing-account" name="hasPreviousAccount" value="{{old('hasPreviousAccount')}}"/>
    <input type="hidden" data-checkbox-id="billing-address" name="hasBillingAddress" data-me="billing-address" value="{{old('hasBillingAddress')}}"/>
    <input type="hidden" data-checkbox-id="has-invoice-comment" name="invoice-comment" value="{{old('invoice-comment')}}"/>
    <input type="hidden" data-checkbox-id="has-fuel-surcharge" name="has-fuel-surcharge" value="{{old('has-fuel-surcharge')}}" />

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
                        <select id="parent-account-id" class='form-control' name="parent-account-id" value="{{old('parent-account-id')}}">
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
                            <option disabled></option>
                            <option value="weekly">Weekly</option>
                            <option value="semi-monthly">Twice a Month</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <hr>
<!-- Driver Commission -->
                    <div class="col-lg-4 well bottom15" id="commission-1-div">
                        <h3 class="panel-title bottom15">Commission 1</h3>
                        <div class="col-lg-6 bottom15">
                            <select id="employee-1-select" class="form-control" type='text' name='commission-employee-1-id' value="{{old('commission-employee-1-id')}}">
                                <option></option>
                                @foreach($model->drivers as $d)
                                    <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input class='form-control' min=0 max=100 type='number' name='commission-1-percent' placeholder="Commission %" value="{{old('commission-1-percent')}}"/>
                        </div>
                        <div><h5>Depreciation rules</h5></div>
                        <hr>
                        <span id="depreciate" class="col-lg-12 form-group">
                            <div class="input-group bottom15">
                                <span class="input-group-addon">Depreciate by</span>
                                <input class="form-control" min=0 max=100 type='number' name='depreciate-1-percentage' placeholder="Depreciation %" value="{{old('depreciate-1-percentage')}}">
                                <span class="input-group-addon"> % </span>
                            </div>
                            <div class="input-group bottom15">
                                <span class="input-group-addon"> for </span>
                                <input class="form-control" min=0 max=100 type='number' name='depreciate-1-duration' placeholder="Depreciation duration" value="{{old('depreciate-1-duration')}}"/>
                                <span class="input-group-addon"> years </span>
                            </div>
                            <div class="input-group bottom15" id="driver-depreciation_start">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> starting </span>
                                <input type='text' id="depreciate-1-start-date" name="depreciate-1-start-date" class="form-control" placeholder="Depreciation start date" value="{{old('depreciate-1-start-date')}}"/>
                            </div>
                        </span>
                    </div>
<!-- Salesman Commission -->
                    <div class="col-lg-4 well bottom15" id="commission-2-div">
                        <h3 class="panel-title bottom15">Commission 2</h3>
                        <div class="col-lg-6 bottom15">
                            <select id="employee-2-select" class="form-control" type='text' name='commission-2-employee-id' value="{{old('commission-2-employee-id')}}">
                                <option></option>
                                @foreach($model->drivers as $d)
                                    <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6 bottom15">
                            <input class='form-control' min=0 max=100 type='number' name='commission-2-percent' placeholder="Commission %" value="{{old('commission-2-percent')}}"/>
                        </div>
                        <div><h5>Depreciation rules</h5></div>
                        <hr>
                        <span id="depreciate" class="col-lg-12 form-group">
                            <div class="input-group bottom15">
                                <span class="input-group-addon">Depreciate by</span>
                                <input class="form-control" min=0 max=100 type='number' name='depreciate-2-percentage' placeholder="Depreciation %" value="{{old('depreciate-2-percentage')}}">
                                <span class="input-group-addon"> % </span>
                            </div>
                            <div class="input-group bottom15">
                                <span class="input-group-addon"> for </span>
                                <input class="form-control" min=0 max=100 type='number' name='depreciate-2-duration' placeholder="Depreciation duration" value="{{old('depreciate-2-duration')}}"/>
                                <span class="input-group-addon"> years </span>
                            </div>
                            <div class="input-group bottom15" id="depreciation_start_date_1">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> starting </span>
                                <input type='text' id="depreciate-2-start-date" name="depreciate-2-start-date" class="form-control" placeholder="Depreciation start date" value="{{old('depreciate-2-start-date')}}"/>
                            </div>
                        </span>
                    </div>
<!-- End Commission -->
                    <div class="col-lg-4 bottom15" id="fuel-surcharge">
                        <input class='form-control' min=0 max=100 type='number' name="fuel-surcharge" placeholder="Fuel surcharge %" value="{{old('fuel-surcharge')}}" />
                    </div>
                    <div class="col-lg-4 bottom15" id="discount-div">
                        <input class='form-control' min=0 max=100 type='number' name='discount' placeholder="Discount %" value="{{old('discount')}}" />
                    </div>
                    <div class="col-lg-4 bottom15" id="old-account">
                        <input class='form-control' type='number' name='account-num' placeholder="Previous Account Number" value="{{old('account-num')}}"/>
                    </div>
                    <div class="col-lg-4 bottom15" id="custom-div">
                        <div class="input-group">
                            <input type='text' class="form-control" name='custom-tracker' placeholder="Tracking Field Name" value="{{old('custom-tracker')}}"/>
                            <span class="input-group-addon"><input type='checkbox' name='custom-tracker-sortable' value="{{old('custom-tracker-sortable')}}"/> Sortable?</span>
                        </div>
                    </div>
                    <div class="col-lg-12 bottom15" id="invoice-comment">
                        <label for="comment">Invoice Comment:</label>
                        <textarea class="form-control" rows="5" name="comment" placeholder="This comment will appear on every invoice sent to the account">{{{Input::old('comment')}}}</textarea>
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
        <label><input id="give-commission-1" type="checkbox" value="" data-div="commission-1-div" data-hidden-name="shouldGiveDriverCommission" />Commission 1</label>
    </div>
    <div class="checkbox">
        <label><input id="give-commission-2" type="checkbox" value="" data-div="commission-2-div" data-hidden-name="shouldGiveSalesCommission" />Commission 2</label>
    </div>
    <div class="checkbox">
        <label><input id="has-invoice-comment" type="checkbox" value="" data-div="invoice-comment" data-hidden-name="invoice-comment" /> Invoice Comment </label>
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
    <div class="checkbox">
        <label><input id="has-fuel-surcharge" type="checkbox" name="" value="" data-div="fuel-surcharge" data-hidden-name="has-fuel-surcharge">Charge Fuel Surcharge</label>
    </div>
</div>
@endsection
