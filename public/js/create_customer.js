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
});

$('#advFilter input[type="checkbox"]').each(function(i,j) { 
	if(j.checked){
		$('tr#' + j.id).fadeIn();
	}
	else{
		$('tr#' + j.id).fadeOut();
	}
});

function validate(){
	var errors = "";
	var check1, check2, check3, warnings;
	var warnings = document.getElementById('errors');
	check1 = document.getElementById('subLocation');
	check2 = document.getElementById('parent_account_id');
	// if (check1.checked == true && check2.value);

	// if (errors == "")
	// 	return true;
	warnings.textContent = errors;
	return true;
}
