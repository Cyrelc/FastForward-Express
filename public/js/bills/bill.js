$(document).ready(function() {

    dateInput('delivery_date');
    comboInput('account_id', 'Select an Account');
    comboInput('pickup_driver_id', 'Select a Pickup Driver');
    comboInput('delivery_driver_id', 'Select a Delivery Driver');
    comboInput('interliner_id', 'Select an Interliner (optional)');

    $('#account_id').change(function(){
    	if ($(this).attr('data-reference-field-name') != undefined) {
    		$("#account").removeClass('col-lg-8');
    		$("#account").addClass('col-lg-4');
	    	document.getElementById("reference_field_name").innerHTML = ($("option:selected", this).attr('data-reference-field-name'));
	    	document.getElementById("reference_field").removeAttribute("hidden");
	    }
    });

	$("#pickup_driver_id").change(function(){
		$("#pickup_driver_commission").val($("option:selected", this).attr('data-driver-commission')*100);
		// TODO - make the second field auto match the first one, if the second field was previously undefined
		if ($("#delivery_driver_id").value == undefined)
			$("#delivery_driver_id").value = $(this).value;
	});

	$("#delivery_driver_id").change(function(){
		$("#delivery_driver_commission").val($("option:selected", this).attr('data-driver-commission')*100);
	});
});
