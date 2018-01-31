$(document).ready(function() {
	var sort_order_list = document.getElementById('sort_order_list');
	Sortable.create(sort_order_list);
});

function storeInvoiceLayout() {
	var listElements = $('#sort_order_list').children();

	for (var i = 0; i < listElements.size(); i++) {
		$('input[name=' + listElements[i].getAttribute('name') + ']').val(i);
	}
	var data = $('#layout-form').serialize();

	$.ajax({
		'url': '/invoices/storeLayout',
		'type': 'POST',
		'data': data,
		'success': function(response) {
			toastr.clear();
			toastr.success(response, 'Success');
		},
		'error': function(response){
			var errorText = '';
			for(var key in response.responseJSON)
				errorText += response.responseJSON[key][0] + '</br>';
			toastr.clear();
			toastr.error(errorText, 'Errors', {'timeOut' : 0, 'extendedTImeout': 0});
		}
	})
}
