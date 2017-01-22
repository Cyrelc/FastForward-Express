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

        comboInput('parent-account-id', 'Select a Parent Account');
        comboInput('driver,select', 'Select a Driver');
        phoneInput("primary-phone1");
        phoneInput("primary-phone2");
        phoneInput("secondary-phone1");
        phoneInput("secondary-phone2");
        zipInput("delivery-zip");
        zipInput("billing-zip");

        <!--Reconstruct all secondary contacts-->
        <?php
            use Illuminate\Support\Facades\Input;
            $oldValues = Input::old();

            foreach($oldValues as $key=>$value) {
                if (substr($key, 0, 6) == "sc-id-") {
                    $id = substr($key, 6);
                    $fName = old('sc-' . $id . '-first-name');
                    $lName = old('sc-' . $id . '-last-name');
                    $ppn = old('sc-' . $id . '-phone1');
                    $spn = old('sc-' . $id . '-phone2');
                    $em = old('sc-' . $id . '-email1');
                    $em2 = old('sc-' . $id . '-email2');

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
<form method="POST" action="/accounts/store">
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
    <input type="hidden" data-me="secondary-contact" data-body="sec-con-body" data-checkbox-id="secondary-contact" name="hasSecondaryContact" value="{{old('hasSecondaryContact')}}"/>

    <div class="well" style="overflow: hidden">
        <!--Basic Information Panel-->
        <div class="row">
            <div class="panel panel-default col-lg-12">
                <div class="panel-body clearfix">
                    <!-- errors go here if submission fails -->
                    @if(!empty($errors) && $errors->count() > 0)
                        <br />
                        <div class="col-lg-12">
                            <div class="alert alert-danger">
                                <p>The following errors occurred on submit:</p>

                                <ul>
                                    @foreach($errors->all() as $message)
                                        <li>{{  $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                    <pre id='errors' class='hidden'></pre>
                    <div id="parent-location" class="bottom15 col-lg-12 clearfix" >
                        <select id="parent-account-id" class='form-control' name="parent-account-id">
                            <option></option>
                            @foreach ($model->accounts as $parent)
                                <option value='{{$parent->account_id}}'>{{$parent->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix bottom15">
                        <input type='text' class="form-control" name="name" placeholder="Company Name" value="{{old('name')}}" />
                    </div>
                    <div class="col-lg-4 clearfix bottom15">
                        <select class='form-control' name="rate-id" disabled >
                            <option value="-1" selected disabled>Select Rate (coming soon!)</option>
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix bottom15">
                        <select class='form-control' name="invoice-interval" value="{{old('invoice-interval')}}">
                            <option value="-1" selected disabled>Select Invoice Interval</option>
                            <option value="weekly">Weekly</option>
                            <option value="semi-monthly">Twice a Month</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="discount-div">
                        <input class='form-control' min=0 max=100 type='number' name='discount' placeholder="Discount %" value="{{old('discount')}}" />
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="commission-div">
                        <div class="split-50">
                            <div class="input-group">
                                <select id="driver-select" class="form-control" type='text' name='commission-employee-id' value="{{old('commission-employee-id')}}">
                                    <option></option>
                                    @foreach($model->drivers as $d)
                                        <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="split-50">
                            <input class='form-control' min=0 max=100 type='number' name='commission-percent' placeholder="Commission %" value="{{old('commission-percent')}}"/>
                        </div>
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="old-account">
                        <input class='form-control' type='number' name='account-num' placeholder="Previous Account Number" />
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="custom-div">
                        <div class="input-group">
                            <input type='text' class="form-control" name='custom-tracker' placeholder="Tracking Field Name" />
                            <span class="input-group-addon"><input type='checkbox' name='custom-tracker-sortable' /> Sortable?</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--Primary Contact Panel -->
        <div class="row row-eq-height">
            <div class='col-lg-6 panel panel-default'>
                <div class="col-lg-12 panel-heading">
                    <h3 class='panel-title'>Primary Contact</h3>
                </div>
                <div class="col-lg-12 panel-body">
                    <div class="clearfix">
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control' name='primary-first-name' placeholder='First Name' value="{{old('primary-first-name')}}" />
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control' name='primary-last-name' placeholder='Last Name' value="{{old('primary-last-name')}}" />
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type="tel" id="primary-phone1" class='form-control' name='primary-phone1' placeholder='Primary Phone' value="{{old('primary-phone1')}}"/>
                        </div>
                        <div class='col-lg-6 clearfix bottom15'>
                            <input type='tel' id="primary-phone2" class='form-control' name='primary-phone2' placeholder='Secondary Phone' value="{{old('primary-phone2')}}"/>
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control' name='primary-email1' placeholder='Primary Email' value="{{old('primary-email1')}}"/>
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control' name='primary-email2' placeholder='Secondary Email' value="{{old('primary-email2')}}"/>
                        </div>
                    </div>
                </div>
            </div>
            <!--Secondary Contact Panel -->
            <div class='col-lg-6 panel panel-default' id="secondary-contacts">

                <ul id="sc-contact-tabs" class="nav nav-pills" role="tablist">
                    <li role="presentation" class="active"><a href="#additional-contacts" aria-controls="home" role="tab" data-toggle="tab">Additional Contacts</a></li>
                    <li role="presentation"><a href="#new-sc-contact" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-plus-circle"></i> Add New</a></li>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content" id="sc-contact-bodies">
                    <div role="tabpanel" class="tab-pane active" id="additional-contacts">
                    </div>
                    <div role="tabpanel" class="tab-pane" id="new-sc-contact">
                        <div class="col-lg-11">
                            <div class="clearfix form-section">
                                <div class="col-lg-6 clearfix bottom15">
                                    <input type='text' class='form-control sec-con-body' id='secondary-first-name' placeholder='First Name'/>
                                </div>
                                <div class="col-lg-6 clearfix bottom15">
                                    <input type='text' class='form-control sec-con-body' id='secondary-last-name' placeholder='Last Name'/>
                                </div>
                                <div class="col-lg-6 clearfix bottom15">
                                    <input type="tel" id="secondary-phone1" class='form-control sec-con-body' id='secondary-phone1' placeholder='Primary Phone'/>
                                </div>
                                <div class='col-lg-6 clearfix bottom15'>
                                    <input class="form-control sec-con-body" id="secondary-phone2" id='secondary-phone2' placeholder='Secondary Phone'/>
                                </div>
                                <div class='col-lg-6 clearfix'>
                                    <input type='email' class='form-control sec-con-body' id='secondary-email1' placeholder='Primary Email'/>
                                </div>
                                <div class='col-lg-6 clearfix'>
                                    <input type='email' class='form-control sec-con-body' id='secondary-email2' placeholder='Secondary Email'/>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <ul class="nav nav-pills">
                                <li title="save">
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
        <!-- Delivery address panel -->
        <div class="row row-eq-height">
            <div class="col-lg-6 panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Delivery Address</h3>
                </div>
                <div class="col-lg-12 panel-body">
                    <div class="form-group clearfix bottom15">
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='delivery-street' placeholder="Address Line 1"  value="{{old('delivery-street')}}"/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' id="delivery-zip" class='form-control' name='delivery-zip-postal' placeholder="Postal/Zip Code"  value="{{old('delivery-zip-postal')}}" />
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control' name='delivery-street2' placeholder="Address Line 2" value="{{old('delivery-street2')}}" />
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control' name='delivery-state-province' placeholder="Province/State" value="{{old('delivery-state-province')}}" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='delivery-city' placeholder="City" value="{{old('delivery-city')}}" />
                        </div>
                        <div class="col-lg-6 clearfix">
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
                    <div class="form-group clearfix">
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control billing-body' name='billing-street' placeholder="Address Line 1" value="{{old('billing-street')}}" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' id="billing-zip" class='form-control billing-body' name='billing-zip-postal' placeholder="Postal/Zip Code" value="{{old('billing-zip-postal')}}" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control billing-body' name='billing-street2' placeholder="Address Line 2" value="{{old('billing-street2')}}" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control billing-body' name='billing-state-province' placeholder="Province/State" value="{{old('billing-state-province')}}" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control billing-body' name='billing-city' placeholder="City" value="{{old('billing-city')}}" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control billing-body' name='billing-country' placeholder="Country" value="{{old('billing-country')}}" disabled/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class='text-center'><button type='submit' class='btn btn-primary'>Submit</button></div>
</form>
@endsection

@section ('advFilter')
<div class="well form-group clearfix">
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
