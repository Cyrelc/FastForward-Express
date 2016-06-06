@extends ('layouts.app')

@section ('script')

<script type="text/javascript">
	
$(document).ready(function() {
	$('#subLocation, #separateBillingAddr, #giveDiscount, #giveCommission, #giveDriverCommission, #balanceOwingInterest, #gstExempt, #useCustomField').change(function() {
		if(this.checked){
			$('tr#' + this.id).fadeIn();
		}
		else{
			$('tr#' + this.id).fadeOut();
		}
	}).change();
})

function showSecondaryContact() {
	$('.secondaryContact').prop('hidden', false);
	$('#secondaryContact-button').prop('hidden', true);
}

// function validateForm() {

// }

</script>

@parent

@endsection

@section ('content')
<form>
	<table class='newCustomer'>
		<thead>
		</thead>
		<tbody>
			<tr id='subLocation' hidden>
				<td><label>Parent Location: </label></td>
				<td><select class='' name=''></td>
			</tr>
			<tr>
				<td><label>Company Name: </label></td>
				<td><input type='' name=''/></td>
			</tr>
			<tr>
				<td><label>Delivery Address: </label></td>
				<td><input type='' name=''></td>
				<td><label>Postal Code: </label></td>
				<td><input type='' name=''></td>
			</tr>
			<tr id='separateBillingAddr' hidden>
				<td><label>Billing Address: </label></td>
				<td><input type='' name=''></td>
				<td><label>Postal Code: </label></td>
				<td><input type='' name=''></td>
			</tr>
			<tr>
				<td><label>Primary Contact: </label></td>
			</tr>
			<tr>
				<td><label>Name:</label>
				<td><input type='' name=''></td>
				<td><label>Primary Phone #: </label></td>
				<td><input type='' name=''></input></td>
				<td><label>Secondary Phone #: </label></td>
				<td><input type='' name=''></td>
			</tr>
			<tr>
				<td><label>Primary Email Address: </label></td>
				<td><input type='' name=''></td>
				<td><label>Secondary Email Address:</label></td>
				<td><input type='' name=''></td>
			</tr>
			<tr>
				<td><button id='secondaryContact-button' type='button' onclick="showSecondaryContact();">Show Secondary Contact</button></td>
			</tr>
			<tr class='secondaryContact' hidden>
				<td><label>Secondary Contact: </label></td>
			</tr>
			<tr class='secondaryContact' hidden>
				<td><label>Name:</label>
				<td><input type='' name=''></td>
				<td><label>Primary Phone #: </label></td>
				<td><input type='' name=''></input></td>
				<td><label>Secondary Phone #: </label></td>
				<td><input type='' name=''></td>
			</tr>
			<tr class='secondaryContact' hidden>
				<td><label>Primary Email Address: </label></td>
				<td><input type='' name=''></td>
				<td><label>Secondary Email Address:</label></td>
				<td><input type='' name=''></td>
			</tr>
			<tr>
				<td><label>Rate Type:</label></td>
				<td><select></select></td>
				<td><label>Invoice Interval:</label></td>
				<td><select></select></td>
			</tr>
			<tr id='giveDiscount' hidden>
				<td><label>Discount:</label></td>
				<td><input min=0 max=100 type='number' name=''></td>
			</tr>
			<tr id='giveCommission' hidden>
				<td><label>Commission ID:</label></td>
				<td><input min=0 type='number' name=''></td>
				<td><label>Commission %</label></td>
				<td><input min=0 max=100 type='number' name=''></td>
			</tr>
			<tr id='giveDriverCommission' hidden>
				<td><label>Driver Commission ID:</label></td>
				<td><input min=0 type='number' name=''></td>
				<td><label>Driver Commission %:</label></td>
				<td><input min=0 max=100 type='number' name=''></td>
			</tr>
			<tr id='useCustomField' hidden>
				<td><label>Custom Field Name:</label></td>
				<td><input type='' name=''></td>
				<td><label><input type='checkbox' name=''>Sortable?</label></td>
			</tr>
		</tbody>
	</table>
</form>
@endsection

@section ('navBar')
<ul class='nav nav-pills nav-stacked'>
	<li class='navButton'><a href="">Save</a></li>
	<li class='navButton'><a href="">Save and New</a></li>
	<li class='navButton'><a href="">Cancel</a></li>
</ul>
@endsection

@section ('advFilter')
	<label><input type='checkbox' id='subLocation' value=''> Is Sub-Location</input></label>
	<label><input type='checkbox' id='separateBillingAddr' value=''> Use Separate Billing Address</input></label>
	<label><input type='checkbox' id='giveDiscount' value=''> Give Discount</input></label>
	<label><input type='checkbox' id='giveCommission' value=''> Give Commission</input></label>
	<label><input type='checkbox' id='giveDriverCommission' value=''> Give Driver Commission</input></label>
	<label><input type='checkbox' id='useCustomField' name='' value=''>Use Custom Field</label>
	<label><input type='checkbox' id='balanceOwingInterest' value=''> Charge Interest on Balance Owing</input></label>
	<label><input type='checkbox' id='gstExempt' value=''> GST Exempt</input></label>
@endsection
