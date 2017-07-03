$(document).ready(function() {

    dateInput('delivery-date');
    comboInput('account-id', 'Select an Account');
    comboInput('pickup_driver_id', 'Select a Pickup Driver');
    comboInput('delivery_driver_id', 'Select a Delivery Driver');
    comboInput('interliner_id', 'Select an Interliner (optional)');

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
