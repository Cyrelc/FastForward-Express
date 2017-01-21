$(document).ready(function() {
	$('#sub-location, #separate-billing-addr, #give-discount, #give-commission, #charge-interest, #gst-exempt, #use-custom-field, #existing-account, #can-be-parent, #existing-account').change(function() {
		if(this.checked){
		    $('#' + $(this).attr('data-div')).fadeIn();
		    $("input[name='" + $(this).attr('data-hidden-name') + "']").val('true');
		}
		else {
            $("input[name='" + $(this).attr('data-hidden-name') + "']").val('false');
		    $('#' + $(this).attr('data-div')).fadeOut();
		}
	});

	$('#sub-location, #separate-billing-addr, #give-discount, #give-commission, #charge-interest, #gst-exempt, #use-custom-field, #existing-account').each(function (i, e) {
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

function validate() {
	var errors = {string: "\0"};
	var check = ['name', 'primary-first-name', 'primary-last-name', 'primary-phone1', 'delivery-street', 'delivery-zip-postal', 'delivery-city', 'delivery-state-province', 'delivery-country'];

	$(':input').parent().removeClass('has-error');

	for (var i = 0; i < check.length; i++) {
		notBlank(check[i], errors);
	}

	if ($('#secondary-contact').is(':checked')) {
		var check = ['secondary-first-name', 'secondary-last-name', 'secondary-phone1'];
		for (var i = 0; i < check.length; i++) {
			notBlank(check[i], errors);
		}
	}

	if ($('#billing-address').is(':checked')) {
		var check = ['billing-street', 'billing-zip-postal', 'billing-city', 'billing-state-province', 'billing-country'];
		for (var i = 0; i < check.length; i++) {
			notBlank(check[i], errors);
		}
	}

//validate Parent Company ID
	if ($('#sub-location').is(':checked') && $('#parent-account-id').find(":selected").val() < 0) {
		errors.string += "Please select a valid Parent Account\n";
		$('#parent-account-id').parent().addClass('has-error');
	}

	if ($('#give-discount').is(':checked') && $('[name="discount"]').val().length == 0) {
		errors.string += "Discount field cannot be empty\n";
		$('[name="discount"]').parent().addClass('has-error');
	}

	if ($('#give-commission').is(':checked') && ($('[name="commission-employee-id"]').val().length == 0 || $('[name="commission-percent"]').val().length == 0)) {
		errors.string += "Both commission employee and amount must not be empty\n";
		$('[name="commission-employee-id"]').parent().addClass('has-error');
		$('[name="commission-percent"]').parent().addClass('has-error');
	}

	if ($('[name="invoice-interval"]').find(':selected').val() < 0) {
		errors.string += "Invalid invoice interval\n";
		$('[name="invoice-interval"]').parent().addClass('has-error');
	}

	if ($('[name="existing-account"]').is(':checked') && $('[name="account-number"]').val().length == 0) {
		errors.string += "Account number cannot be empty\n";
		$('[name="account-number"]').parent().addClass('has-error');
	}

	if (errors.string == "\0") {
		return true;
	}
	$('#errors').removeClass('hidden');
	$('#errors').text(errors.string);
	return false;
}

/*Multiple secondary contacts stuff*/
function saveScContact() {
    var fName = $("#secondary-first-name").val();
    var lName = $("#secondary-last-name").val();
    var sPpn = $("#secondary-phone1").val();
    var sSpn = $("#secondary-phone2").val();
    var sem = $("#secondary-email1").val();
    var sem2 = $("#secondary-email2").val();

    var id = -1;
    $("input[data-sc-contact-id='true']").each(function(i, e){
        var newId = $(e).val();

        if (newId > id)
            id = newId;

        id++;
    });

    if (id == -1)
        id = 1;

    newTabPill(id, fName,lName);
    newTabBody(id, fName, lName, sPpn, sSpn, sem, sem2);
    clearScForm();
}

function newTabPill(id, fName, lName) {
    var pill = "<li role='presentation'><a href='#" + id + "-panel' aria-controls='" + id + "' role='tab' data-toggle='tab'>" + fName + " " + lName + "</a></li>";

    $("#sc-contact-tabs").append(pill);
}

function newTabBody(id, fName, lName, sPpn, sSpn, sem, sem2) {
    var body =
        '<div role="tabpanel" class="tab-pane" id="' + id + '-panel">' +
			'<input type="hidden" name="sc-id-' + id + '" data-sc-contact-id="true" value="' + id +  '" />' +
			'<div class="col-lg-11">' +
				'<div class="clearfix form-section">' +
					'<div class="col-lg-6 clearfix bottom15">' +
						'<input type="text" class="form-control sec-con-body" name="sc-' + id + '-first-name" placeholder="First Name" value="' + fName + '"/>' +
					'</div>' +
					'<div class="col-lg-6 clearfix bottom15">' +
						'<input type="text" class="form-control sec-con-body" name="sc-' + id + '-last-name" placeholder="Last Name" value="' + lName + '"/>' +
					'</div>' +
					'<div class="col-lg-6 clearfix bottom15">' +
						'<input type="tel" id="secondary-phone1" class="form-control sec-con-body" name="sc-' + id + '-phone1" placeholder="Primary Phone" value="' + sPpn + '"/>' +
					'</div>' +
					'<div class="col-lg-6 clearfix bottom15">' +
						'<input class="form-control sec-con-body" id="secondary-phone2" name="sc-' + id + '-phone2" placeholder="Secondary Phone" value="' + sSpn + '"/>' +
					'</div>' +
					'<div class="col-lg-6 clearfix">' +
						'<input type="email" class="form-control sec-con-body" name="sc-' + id + '-email1" placeholder="Primary Email" value="' + sem + '"/>' +
					'</div>' +
					'<div class="col-lg-6 clearfix">' +
						'<input type="email" class="form-control sec-con-body" name="sc-' + id + '-email2" placeholder="Secondary Email" value="' + sem2 + '" />' +
					'</div>' +
				'</div>' +
			'</div>' +
			'<div class="col-lg-1">' +
				'<ul class="nav nav-pills">' +
					'<li title="delete">' +
						'<a href="javascript:removeSc(' + id + ')"><i class="fa fa-trash"></i></a>' +
					'</li>' +
				'</ul>' +
			'</div>' +
        '</div>';

    $("#sc-contact-bodies").append(body);
}

function clearScForm() {
    $("#secondary-first-name").val('');
    $("#secondary-last-name").val('');
    $("#secondary-phone1").val('');
    $("#secondary-phone2").val('');
    $("#secondary-email1").val('');
    $("#secondary-email2").val('');
}

function removeSc(id) {
    var selector = "#sc-contact-tabs a[href='" + id + "-panel']";
    $("#sc-contact-tabs a[href='#" + id + "-panel']").parent().remove();
    $("#" + id + '-panel').remove();
}