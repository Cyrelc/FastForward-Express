@extends ('layouts.app')

@section ('script')

<script type='text/javascript' src='/js/create_customer.js'></script>

@parent

@endsection

@section ('style')

<style type="text/css">
    input, select, textarea {
        max-width: 280px;
    }

    .default-hidden {
        display: none;
    }

    .form-section {
        margin: 20px 0 0 0;
    }

    .form-section h4 {
        margin-bottom: 15px;
    }

    .form-section input {
        margin-bottom: 10px;
    }
</style>

@endsection

@section ('content')  
<h2>New Customer</h2>
<form onsubmit="return validate()" method="POST" action="/customers/store">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div class="well form-group" style="overflow: hidden">
        <!--Basic Information Panel-->
        <div class="row">
            <div class="panel panel-default col-lg-12">
                <div class="panel-body clearfix">
                    <!-- errors go here if submission fails -->
                    <p id='errors'></p>
                    <div id="parentLocation" class="col-lg-12 clearfix form-group" >
                        <select id="parent_account_ID" class='form-control'>
                            <option value="-1" selected disabled>Select Parent Company</option>
                            @foreach ($parents as $parent)
                                <option value={{$parent->account_id}}>{{$parent->name}}</option> 
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix form-group">
                        <input type='text' class="form-control" name="name" placeholder="Company Name" required/>
                    </div>
                    <div class="col-lg-4 clearfix form-group">
                        <select class='form-control' disabled required>
                            <option value="-1" selected disabled>Select Rate</option>
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix form-group">
                        <select class='form-control' disabled required>
                            <option value="-1" selected disabled>Select Invoice Interval</option>
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix" id="discountDiv">
                        <input class='form-control' min=0 max=100 type='number' name='discount' placeholder="Discount %" />
                    </div>
                    <div class="col-lg-4 clearfix" id="commissionDiv">
                        <div class="col-lg-8 clearfix">
                            <input class='form-control' type='text' name='' placeholder="Driver" />
                        </div>
                        <div class="col-lg-4 clearfix">
                            <input class='form-control' min=0 max=100 type='number' name='' placeholder="Commission %"/>
                        </div>
                    </div>
                    <div class="col-lg-4 clearfix" id="customDiv">
                        <span><input type='text' class="form-control" name='' placeholder="Custom Div Name" /></span>
                        <span><label><input type='checkbox' class="form-control clearfix" name='' />Sortable?</label></span>
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
                    <div class="form-group clearfix form-section">
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='first_name1' placeholder='First Name' />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='last_name1' placeholder='Last Name' />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type="tel" class='form-control' name='primary_phone1' placeholder='Primary Phone' />
                        </div>
                        <div class='col-lg-6 clearfix'>
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
                    <h3 class='panel-title'>Secondary Contact</h3>
                </div>
                <div class="col-lg-12 panel-body">
                    <div class="form-group clearfix form-section">
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='first_name' placeholder='First Name' />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='last_name' placeholder='Last Name' />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type="tel" class='form-control' name='primary_phone' placeholder='Primary Phone' />
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='tel' pattern="[0-9]{10}" class='form-control' name='secondary_phone' placeholder='Secondary Phone' />
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control' name='primary_email' placeholder='Primary Email' />
                        </div>
                        <div class='col-lg-6 clearfix'>
                            <input type='email' class='form-control' name='secondary_email' placeholder='SecondaryEmail' />
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
                    <div class="form-group clearfix form-section">
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='street' placeholder="Address Line 1" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='zip_postal' placeholder="Postal/Zip Code" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='street2' placeholder="Address Line 2" />
                        </div>
                        <div class="col-lg-6">
                            <input type='text' class='form-control' name='state_province' placeholder="Province/State" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='city' placeholder="City" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='country' placeholder="Country" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 panel panel-default">
                <div class="col-lg-12 panel-heading">
                    <span><h3 class="panel-title">Billing Address</h3></span>
                </div>
                <div class="col-lg-12 panel-body">
                    <div class="form-group clearfix form-section">
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='street' placeholder="Address Line 1" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='zip_postal' placeholder="Postal/Zip Code" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='street2' placeholder="Address Line 2" />
                        </div>
                        <div class="col-lg-6">
                            <input type='text' class='form-control' name='state_province' placeholder="Province/State" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='city' placeholder="City" />
                        </div>
                        <div class="col-lg-6 clearfix">
                            <input type='text' class='form-control' name='country' placeholder="Country" />
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
</div>
@endsection
