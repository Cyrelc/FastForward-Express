$(document).ready(function(){
    dateInput('dob-picker');
    dateInput('startdate-picker');

	$("#sin").keydown(function(e){numberFilter(e);});

    new Cleave('#sin', {
        delimiter: ' ',
        blocks: [3, 3, 3]
    });

    $("#is_driver_checkbox").change(function(){
    	if($(this).prop('checked'))
    		enableDriver();
    	else 
    		disableDriver();
    });

    $("#is_sales_checkbox").change(function(){
    	if($(this).prop('checked'))
    		enableSales();
    	else
    		disableSales();
    })

	if($("#is_driver").val() == "true") {
		enableDriver();
	}

	if($("#is_sales").val() == "true") 
		enableSales();
});

function storeEmployee() {
	var data = $('#employee-form').serialize();

	$.ajax({
		'url': '/employees/store',
		'type': 'POST',
		'data': data,
		'success': function(){
			var isEdit = typeof($('#employee_id').val()) === 'undefined' ? false : true;
			console.log(isEdit);
			var employeeName = $('#employee-first-name').val() + ' ' + $('#employee-last-name').val();
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
		'error': function(response){
			console.log(response);
			var errorText = '';
			for(var key in response.responseJSON){
				errorText += response.responseJSON[key][0] + '</br>';
			}
			toastr.error(errorText, 'Errors', {'timeOut': 0, 'extendedTImeout': 0, 'positionClass': 'toast-top-full-width'})
		}
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
