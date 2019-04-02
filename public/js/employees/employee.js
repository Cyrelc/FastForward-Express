$('document').ready(function(){
    dateInput('dob-picker');
    dateInput('startdate-picker');

	$("#sin").keydown(function(e){numberFilter(e);});

    new Cleave('#sin', {
        delimiter: ' ',
        blocks: [3, 3, 3]
	});
	
	cleave();
});

function storeEmployee(button) {
    var radio = $('#employee_contact_form input:radio[name="email_is_primary[]"]');
    var primaryIndex = radio.index(radio.filter(':checked'));
    radio.filter(':checked').val(primaryIndex);
    radio = $('#employee_contact_form input:radio[name="phone_is_primary[]"]');
    primaryIndex = radio.index(radio.filter(':checked'));
    radio.filter(':checked').val(primaryIndex);

	var data = $('#employee_contact_form, #employee_driver_form, #employee_admin_form').serialize();

	$.ajax({
		'url': '/employees/store',
		'type': 'POST',
		'data': data,
		'success': function(){
			var isEdit = typeof($('#employee_id').val()) === 'undefined' ? false : true;
			toastr.clear();
			var employeeName = $('#employee_contact_form').find('#first_name').val() + ' ' + $('#employee_contact_form').find('#last_name').val();
			if (isEdit) {
				toastr.success(employeeName + ' successfully updated!', 'Success');
			} else {
				toastr.success(employeeName + ' was successfully created', 'Success', {
					'progressBar': true,
					'positionClass': 'toast-top-full-width',
					'showDuration': 500,
					'onHidden': function(){location.reload()}
				})
			}
		},
		'error': function(response){handleErrorResponse(response)}
	})
}

function enableSales() {
	//TODO: create sales type of employee, with default commission settings for that employee (see commissions partial blade)
}

function enableDriver() {
	dateInput('license-picker');
	dateInput('lp-picker');
	dateInput('insurance-picker');

	$('#dln').keydown(function(e){numberFilter(e);});

    new Cleave('#dln', {
        delimiter: '-',
		blocks: [6, 3]
	});

	$('#driver_form_button').removeClass('hidden');
	$('#is_driver').val(true);
}

function disableDriver() {
	$('#driver_form_button').addClass('hidden');
	$('#is_driver').val('false');
	if($('#driver').hasClass('active')) {
		$('#driver').removeClass('active');
		$('#main').addClass('active');
	}
	$('#is_driver').val(false);
}
