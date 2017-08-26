$(document).ready(function() {
	var checkboxes = '#send-bills, #sub-location, #give-discount, #give-commission-1, #give-commission-2, #has-fuel-surcharge, #charge-interest, #gst-exempt, #use-custom-field, #existing-account, #can-be-parent, #existing-account';
	$(checkboxes).change(function() {
		if(this.checked){
            $("input[name='" + $(this).attr('data-hidden-name') + "']").val('true');
            $('#' + $(this).attr('data-div')).fadeIn();
		}
		else {
            $("input[name='" + $(this).attr('data-hidden-name') + "']").val('false');
		    $('#' + $(this).attr('data-div')).fadeOut();
		}
	});

	$(checkboxes).each(function (i, e) {
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
	return true;
}
