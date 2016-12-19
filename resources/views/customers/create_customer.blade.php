@extends ('layouts.app')

@section ('script')

<script type='text/javascript' src='public/dist/jquery.validate.js'></script>

<script type="text/javascript">
$(document).ready(function() {
	$('#subLocation, #separateBillingAddr, #giveDiscount, #giveCommission, #giveDriverCommission, #balanceOwingInterest, #gstExempt, #useCustomField').change(function() {
		if(this.checked){
		    $('#' + $(this).attr('data-div')).fadeIn();
		}
		else {
		    $('#' + $(this).attr('data-div')).fadeOut();
		}
	});

	$('#subLocation, #separateBillingAddr, #giveDiscount, #giveCommission, #giveDriverCommission, #balanceOwingInterest, #gstExempt, #useCustomField').each(function (i, e) {
	    $("#" + $(this).attr('data-div')).css('display', 'none');
	});
})

	$('#advFilter input[type="checkbox"]').each(function(i,j) { 
		if(j.checked){
			$('tr#' + j.id).fadeIn();
		}
		else{
			$('tr#' + j.id).fadeOut();
		}
	});

function showSecondaryContact() {
	$('.secondaryContact').prop('hidden', false);
	$('#secondaryContact-button').prop('hidden', true);
}
</script>

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
<form data-toggle="validator" method="POST" action="/customers/store">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div class="well form-group" style="overflow: hidden">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="clearfix">
                    <div class="col-lg-12 clearfix form-group" >
                        <select id="parent_account_ID" class='form-control'>
                            <option value="" name="parent_account_id" selected disabled>Select Parent Company</option>
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix form-group">
                        <input type='text' class="form-control" name="name" placeholder="Company Name" / required>
                    </div>
                    <div class="col-lg-4 clearfix form-group">
                        <select class='form-control' >
                            <option value="" selected disabled required>Select Rate</option>
                        </select>
                    </div>
                    <div class="col-lg-4 clearfix form-group">
                        <select class='form-control' >
                            <option value="" selected disabled>Select Invoice Interval</option>
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
                        <input type='text' class="form-control" name='' placeholder="Custom Div Name" />
                        <label><input type='checkbox' class="form-control clearfix" name='' />Sortable?</label>
                    </div>
                </div>
            </div>
        </div>

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
                        <input type="tel" class='form-control' name='primary_phone1' placeholder='Primary Phone' />
                    </div>
                    <div class="col-lg-6 clearfix">
                        <input type='text' class='form-control' name='last_name1' placeholder='Last Name' />
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
                        <input type="tel" class='form-control' name='primary_phone' placeholder='Primary Phone' />
                    </div>
                    <div class="col-lg-6 clearfix">
                        <input type='text' class='form-control' name='last_name' placeholder='Last Name' />
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

        <!-- Delivery address panel -->
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
                <h3 class="panel-title">Billing Address</h3>
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
    <div class='text-center'><button type='submit' class='btn btn-primary'>Submit</button></div>
</form>
@endsection

@section ('advFilter')
<div class="form-group clearfix">
    <label><input type='checkbox' id='subLocation' value='' data-div="parentLocation" /> Is Sub-Location</label>
    <label><input type='checkbox' id='giveDiscount' value='' data-div="discountDiv" /> Give Discount</label>
    <label><input type='checkbox' id='giveCommission' value='' data-div="commissionDiv" /> Give Commission</label>
    <label><input type='checkbox' id='giveDriverCommission' value='' data-div="driverCommissionDiv" /> Give Driver Commission</label>
    <label><input type='checkbox' id='useCustomField' name='' value='' data-div="customDiv"/>Use Custom Field</label>
    <label><input type='checkbox' id='balanceOwingInterest' value='' /> Charge Interest on Balance Owing</label>
    <label><input type='checkbox' id='gstExempt' value='' /> GST Exempt</label>
</div>
@endsection
