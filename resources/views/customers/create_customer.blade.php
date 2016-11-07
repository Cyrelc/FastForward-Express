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
             
<form>	
    <div class="well">
        <div class="form-group clearfix">
            <div class="col-lg-2" id="parentLocationDiv">
                <input type="text" class="form-control" name="" placeholder="Parent Company" />
            </div>

            <div class="col-lg-10">
                <input type='text' class="form-control" name="" placeholder="Company Name" />
            </div>
        </div>

        <div class="form-group clearfix form-section">
            <h4>Delivery Address</h4>

            <div class="col-lg-12 clearfix">
                <input type='text' class='form-control' name='street' placeholder="Address Line 1" />
            </div>

            <div class="col-lg-12 clearfix">
                <input type='text' class='form-control' name='street2' placeholder="Address Line 2" />
            </div>

            <div class="col-lg-12 clearfix">
                <input type='text' class='form-control' name='city' placeholder="City" />
            </div>

            <div class="clearfix">
                <div class="col-lg-4">
                    <input type='text' class='form-control' name='zip_postal' placeholder="Postal/Zip Code" />
                </div>

                <div class="col-lg-2">
                    <input type='text' class='form-control' name='state_province' placeholder="Province/State" />
                </div>
            </div>

            <div class="col-lg-12 clearfix">
                <input type='text' class='form-control' name='country' placeholder="Country" />
            </div>

            <div class="form-group clearfix" id="billingAddressDiv">
                <label>Billing Address: </label>
                <input type='text' name='' />
                <label>Postal Code: </label>
                <input type='text' name='' />
            </div>
        </div>

        <label>Primary Contact: </label>


        <label>Name:</label>
        <input type='text' name='' />
        <label>Primary Phone #: </label>
        <input type='text' name='' />
        <label>Secondary Phone #: </label>
        <input type='text' name='' />


        <label>Primary Email Address: </label>
        <input type='text' name='' />
        <label>Secondary Email Address:</label>
        <input type='text' name='' />


        <button id='secondaryContact-button' type='button' onclick="showSecondaryContact();">Show Secondary Contact</button>

        <label>Secondary Contact: </label>

        <label>Name:</label>
        <input type='text' name='' />
        <label>Primary Phone #: </label>
        <input type='text' name='' />
        <label>Secondary Phone #: </label>
        <input type='text' name='' />

        <label>Primary Email Address: </label>
        <input type='text' name='' />
        <label>Secondary Email Address:</label>
        <input type='text' name='' />


        <label>Rate Type:</label>
        <select></select>
        <label>Invoice Interval:</label>
        <select></select>

        <div class="form-group clearfix" id="discountDiv">
            <label class="col-lg-2">Discount:</label>
            <div class="col-lg-10">
                <input min=0 max=100 type='number' name='' />
            </div>
        </div>

        <div class="form-group clearfix" id="commissionDiv">
            <label>Commission ID:</label>
            <input min=0 type='number' name='' />
            <label>Commission %</label>
            <input min=0 max=100 type='number' name='' />
        </div>

        <div class="form-group clearfix" id="driverCommissionDiv">
            <label>Driver Commission ID:</label>
            <input min=0 type='number' name='' />
            <label>Driver Commission %:</label>
            <input min=0 max=100 type='number' name='' />
        </div>

        <div class="form-group clearfix" id="customDiv">
            <label>Custom Field Name:</label>
            <input type='text' name='' />
            <label>
                <input type='checkbox' name='' />Sortable?
            </label>
        </div>
    </div>

</form>
@endsection

@section ('navBar')
<ul class='nav nav-pills nav-stacked'>
	<li class='navButton'><a href=""><i class="fa fa-save"></i> Save</a></li>
	<li class='navButton'><a href=""><i class="fa fa-plus-square-o"></i> Save and New</a></li>
	<li class='navButton'><a href=""><i class="fa fa-ban"></i> Cancel</a></li>
</ul>
@endsection

@section ('advFilter')

    <div class="checkbox">
	    <label><input type='checkbox' id='subLocation' value='' data-div="parentLocationDiv" /> Is Sub-Location</label>
    </div>

    <div class="checkbox">
        <label><input type='checkbox' id='separateBillingAddr' value='' data-div="billingAddressDiv" /> Use Separate Billing Address</label>
    </div>

    <div class="checkbox">
        <label><input type='checkbox' id='giveDiscount' value='' data-div="discountDiv" /> Give Discount</label>
    </div>

    <div class="checkbox">
        <label><input type='checkbox' id='giveCommission' value='' data-div="commissionDiv" /> Give Commission</label>
    </div>

    <div class="checkbox">
        <label><input type='checkbox' id='giveDriverCommission' value='' data-div="driverCommissionDiv" /> Give Driver Commission</label>
    </div>

    <div class="checkbox">
        <label><input type='checkbox' id='useCustomField' name='' value='' data-div="customDiv"/>Use Custom Field</label>
    </div>

    <div class="checkbox">
        <label><input type='checkbox' id='balanceOwingInterest' value='' /> Charge Interest on Balance Owing</label>
    </div>

    <div class="checkbox">
        <label><input type='checkbox' id='gstExempt' value='' /> GST Exempt</label>
    </div>                        
@endsection
