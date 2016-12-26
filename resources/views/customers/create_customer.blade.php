@extends ('layouts.app')

@section ('script')

<script type='text/javascript' src='/js/validation.js'></script>
<script type='text/javascript' src='/js/create_customer.js'></script>

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
<h2>New Customer</h2>
<form onsubmit="return validate()" method="POST" action="/customers/store">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div class="well" style="overflow: hidden">
        <!--Basic Information Panel-->
        <div class="row">
            <div class="panel panel-default col-lg-12">
                <div class="panel-body clearfix">
                    <!-- errors go here if submission fails -->
                    <pre id='errors' class='hidden'></pre>
                    <div id="parentLocation" class="bottom15 col-lg-12 clearfix" >
                        <select id="parent_account_id" class='form-control col-lg-4' name="parent_account_id">
                            <option value="-1" selected disabled>Select Parent Company</option>
                            @foreach ($parents as $parent)
                                <option value={{$parent->account_id}}>{{$parent->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix bottom15">
                        <input type='text' class="form-control" name="name" placeholder="Company Name" />
                    </div>
                    <div class="col-lg-4 clearfix bottom15">
                        <select class='form-control' name="rate_id" disabled >
                            <option value="-1" selected disabled>Select Rate (coming soon!)</option>
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix bottom15">
                        <select class='form-control' name="invoice_interval" >
                            <option value="-1" selected disabled>Select Invoice Interval</option>
                            <option value="weekly" >Weekly</option>
                            <option value="semi-monthly">Twice a Month</option>
                            <option value="montly">Monthly</option>
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="discountDiv">
                        <input class='form-control' min=0 max=100 type='number' name='discount' placeholder="Discount %" />
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="commissionDiv">
                        <div class="col-lg-8 clearfix">
                            <input class='form-control' type='text' name='commission_employee_id' placeholder="Employee" />
                        </div>
                        <div class="col-lg-4 clearfix bottom15">
                            <input class='form-control' min=0 max=100 type='number' name='commission_percent' placeholder="Commission %"/>
                        </div>
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="old_account">
                        <input class='form-control' type='number' name='account_number' placeholder="Previous Account Number" />
                    </div>
                    <div class="col-lg-4 clearfix bottom15" id="customDiv">
                        <span><input type='text' class="form-control" name='custom_tracker' placeholder="Custom Div Name" /></span>
                        <span><label><input type='checkbox' class="form-control clearfix" name='custom_tracker_sortable' />Sortable?</label></span>
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
                            <input type='text' class='form-control' name='first_name1' placeholder='First Name' />
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control' name='last_name1' placeholder='Last Name' />
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type="tel" class='form-control' name='primary_phone1' placeholder='Primary Phone' />
                        </div>
                        <div class='col-lg-6 clearfix bottom15'>
                            <input type='tel' class='form-control' name='secondary_phone1' placeholder='Secondary Phone' />
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control' name='primary_email1' placeholder='Primary Email' />
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control' name='secondary_email1' placeholder='Secondary Email' />
                        </div>
                    </div>
                </div>
            </div>
            <!--Secondary Contact Panel -->
            <div class='col-lg-6 panel panel-default'>
                <div class="col-lg-12 panel-heading">
                    <h3 class='panel-title'><input type="checkbox" id="secondary_contact" onclick="enableBody(this.id, 'sec_con_body')">Secondary Contact</h3>
                </div>
                <div class="col-lg-12 panel-body">
                    <div class="clearfix form-section">
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control sec_con_body' name='first_name2' placeholder='First Name' disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control sec_con_body' name='last_name2' placeholder='Last Name' disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type="tel" class='form-control sec_con_body' name='primary_phone2' placeholder='Primary Phone' disabled/>
                        </div>
                        <div class='col-lg-6 clearfix bottom15'>
                            <input class="form-control sec_con_body" name='secondary_phone2' placeholder='Secondary Phone' disabled/>
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control sec_con_body' name='primary_email2' placeholder='Primary Email' disabled/>
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control sec_con_body' name='secondary_email2' placeholder='SecondaryEmail' disabled/>
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
                            <input type='text' class='form-control' name='street_delivery' placeholder="Address Line 1" />
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control' name='zip_postal_delivery' placeholder="Postal/Zip Code" />
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control' name='street2_delivery' placeholder="Address Line 2" />
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control' name='state_province_delivery' placeholder="Province/State" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='city_delivery' placeholder="City" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='country_delivery' placeholder="Country" />
                        </div>
                    </div>
                </div>
            </div>
            <!-- Billing address panel -->
            <div class="col-lg-6 panel panel-default">
                <div class="col-lg-12 panel-heading">
                    <h3 class="panel-title"><input type='checkbox' id='billing_address' onclick="enableBody(this.id, 'billing_body')" />Billing Address</h3>
                </div>
                <div class="col-lg-12 panel-body">
                    <div id="billing_body" class="form-group clearfix form-section">
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control billing_body' name='street_billing' placeholder="Address Line 1" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control billing_body' name='zip_postal_billing' placeholder="Postal/Zip Code" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control billing_body' name='street2_billing' placeholder="Address Line 2" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix bottom15">
                            <input type='text' class='form-control billing_body' name='state_province_billing' placeholder="Province/State" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control billing_body' name='city_billing' placeholder="City" disabled/>
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control billing_body' name='country_billing' placeholder="Country" disabled/>
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
        <label><input id="subLocation" type="checkbox" value="" name="subLocationCheckBox" data-div="parentLocation" />Is Sub-Location</label>
    </div>
    <div class="checkbox">
        <label><input id="giveDiscount" type="checkbox" value="" data-div="discountDiv" />Give Discount</label>
    </div>
    <div class="checkbox">
        <label><input id="giveCommission" type="checkbox" value="" data-div="commissionDiv" />Give Commission</label>
    </div>
    <div class="checkbox">
        <label><input id="giveDriverCommission" type="checkbox" value="" data-div="driverCommissionDiv" />Give Driver Commission</label>
    </div>
    <div class="checkbox">
        <label><input id="useCustomField" type="checkbox" value="" data-div="customDiv" />Use Custom Field</label>
    </div>
    <div class="checkbox">
        <label><input id="balanceOwingInterest" type="checkbox" value="" />Charge Interest on Balance Owing</label>
    </div>
    <div class="checkbox">
        <label><input id="gstExempt" type="checkbox" value="">Is GST Exempt</label>
    </div>
    <div class="checkbox">
        <label><input id="canBeParent" type="checkbox" value="">Can be Parent</label>
    </div>
    <div class="checkbox">
        <label><input id="existing_account" type="checkbox" name="" value="" data-div="old_account">Previous Account</label>
    </div>
</div>
@endsection
