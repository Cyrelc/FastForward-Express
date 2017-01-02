@extends ('layouts.app')

@section ('script')

<script type='text/javascript' src='/js/validation.js'></script>
<script type='text/javascript' src='/js/create_account.js'></script>
<script type='text/javascript'>
    $(document).ready(function(){
        //On failed validation, redisplay form correctly
        @if(old('secondary-contact') == 'on')
            $('#secondary-contact').prop('checked', 'true');
            enableBody('secondary-contact', 'sec-con-body');
        @endif

        @if(old('billing-address') == 'on')
            $('#billing-address').prop('checked', 'true');
            enableBody('billing-address', 'billing-body');
        @endif
    });
</script>

@parent

@endsection

@section ('style')

<style type="text/css">
#errors {
    color: red;
}
</style>

@endsection

@section ('content')  
<h2>New Account</h2>
<form onsubmit="return validate()" method="POST" action="/accounts/store">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
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
                        <select id="parent-account-id" class='form-control col-lg-4' name="parent-account-id">
                            <option value="-1" selected disabled>Select Parent Company</option>
                            @foreach ($parents as $parent)
                                <option value='{{$parent->account-id}}'>{{$parent->name}}</option>
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
                        <select class='form-control' name="invoice-interval" >
                            <option value="-1" selected disabled>Select Invoice Interval</option>
                            <option value="weekly">Weekly</option>
                            <option value="semi-monthly">Twice a Month</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="discount-div">
                        <input class='form-control' min=0 max=100 type='number' name='discount' placeholder="Discount %" />
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="commission-div">
                        <div class="col-lg-8 clearfix">
                            <input class='form-control' type='text' name='commission-employee-id' placeholder="Employee" />
                        </div>
                        <div class="col-lg-4 clearfix bottom15">
                            <input class='form-control' min=0 max=100 type='number' name='commission-percent' placeholder="Commission %"/>
                        </div>
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="old-account">
                        <input class='form-control' type='number' name='account-num' placeholder="Previous Account Number" />
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="custom-div">
                        <span><input type='text' class="form-control" name='custom-tracker' placeholder="Custom Div Name" /></span>
                        <span><label><input type='checkbox' class="form-control clearfix" name='custom-tracker-sortable' />Sortable?</label></span>
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
                            <input type="tel" class='form-control' name='primary-phone1' placeholder='Primary Phone' value="{{old('primary-phone1')}}"/>
                        </div>
                        <div class='col-lg-6 clearfix bottom15'>
                            <input type='tel' class='form-control' name='primary-phone2' placeholder='Secondary Phone' value="{{old('primary-phone2')}}"/>
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
            <div class='col-lg-6 panel panel-default'>
                <div class="col-lg-12 panel-heading">
                    <h3 class='panel-title'>
                        <label style="font-weight: normal;">
                            <input type="checkbox" id="secondary-contact" name="secondary-contact" onclick="enableBody(this.id, 'sec-con-body')"> Secondary Contact
                        </label>
                    </h3>
                </div>
                <div class="col-lg-12 panel-body">
                    <div class="clearfix form-section">
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control sec-con-body' name='secondary-first-name' placeholder='First Name' value="{{old('secondary-first-name')}}" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control sec-con-body' name='secondary-last-name' placeholder='Last Name' value="{{old('secondary-last-name')}}" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type="tel" class='form-control sec-con-body' name='secondary-phone1' placeholder='Primary Phone' value="{{old('secondary-phone1')}}" disabled/>
                        </div>
                        <div class='col-lg-6 clearfix bottom15'>
                            <input class="form-control sec-con-body" name='secondary-phone2' placeholder='Secondary Phone' value="{{old('secondary-phone2')}}" disabled/>
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control sec-con-body' name='secondary-email1' placeholder='Primary Email' value="{{old('secondary-email1')}}" disabled/>
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control sec-con-body' name='secondary-email2' placeholder='Secondary Email' value="{{old('secondary-email2')}}" disabled/>
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
                            <input type='text' class='form-control' name='delivery-zip-postal' placeholder="Postal/Zip Code"  value="{{old('delivery-zip-postal')}}" />
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
                            <input type='text' class='form-control billing-body' name='billing-zip-postal' placeholder="Postal/Zip Code" value="{{old('billing-zip-postal')}}" disabled/>
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
        <label><input id="sub-location" type="checkbox" value="" name="sub-location" data-div="parent-location" />Is Sub-Location</label>
    </div>
    <div class="checkbox">
        <label><input id="give-discount" type="checkbox" value="" data-div="discount-div" />Give Discount</label>
    </div>
    <div class="checkbox">
        <label><input id="give-commission" type="checkbox" value="" data-div="commission-div" />Give Commission</label>
    </div>
    <div class="checkbox">
        <label><input id="use-custom-field" type="checkbox" value="" data-div="custom-div" />Use Custom Field</label>
    </div>
    <div class="checkbox">
        <label><input id="charge-interest" type="checkbox" value="" />Charge Interest on Balance Owing</label>
    </div>
    <div class="checkbox">
        <label><input id="gst-exempt" type="checkbox" value="">Is GST Exempt</label>
    </div>
    <div class="checkbox">
        <label><input id="can-be-parent" type="checkbox" name='can-be-parent' value="">Can be Parent</label>
    </div>
    <div class="checkbox">
        <label><input id="existing-account" type="checkbox" name="" value="" data-div="old-account">Previous Account</label>
    </div>
</div>
@endsection
