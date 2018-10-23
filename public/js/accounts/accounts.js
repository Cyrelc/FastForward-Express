$(document).ready(function() {

    var table = $('#table').DataTable( {
        ajax: {'url':'/accounts/buildTable', 'dataSrc':''},
        dom: 'f<"columnVis"B>lrtip',
        buttons: [{extend: 'print', exportOptions: {columns: ':visible'}},'colvis'],
        columnDefs: [{'sWidth':'35px', 'aTargets':[0]}, {searchable: false, targets: 1}],
        pageLength: 50,
        order: [1, 'asc'],
        createdRow: function (row, data, index) {
            var editButton = '<a class="fa fa-edit btn btn-default btn-xs" title="Edit Account" href="/accounts/edit/' + data.account_id + '" />';
            var deactivateButton = '<button class="fa fa-ban btn btn-danger btn-xs" title="Deactivate Account" onclick="deactivateAccount(this, ' + data.account_id + ')"></button>';
            var activateButton = '<button class="fa fa-play-circle btn btn-success btn-xs" title="Activate Account" onclick="activateAccount(this, ' + data.account_id + ')"></button>';
            $actiontd = $('td', row).eq(0);
            $actiontd.html('<div class="hover-div" >' + editButton + activateButton + deactivateButton + '</div>');
            if(data.active == true)
                $actiontd.find('button.fa-play-circle').hide();
            else
                $actiontd.find('button.fa-ban').hide();
            if(data.parent_id != null)
                $('td', row).eq(3).html('<a href="/accounts/edit/' + data.parent_id + '" >' + data.parent_name + '</a>');
            $('td', row).eq(4).html('<a href="/accounts/edit/' + data.account_id + '" >' + data.name + '</a>');
        },
        columns: [
            {className:'actions', orderable: false, data: null, defaultContent: ''},
            {'data': 'account_id'},
            {'data': 'account_number'},
            {'data': 'parent_name'},
            {'data': 'name'},
            {'data': 'invoice_interval'},
            {'data': 'primary_contact_name'},
            {'data': 'shipping_address_name'},
            {'data': 'billing_address_name'}
        ]
    });
    // table.on( 'xhr', function ( e, settings, json ) {
    //     console.log( 'Ajax event occurred. Returned data: ', json );
    // });
});

function activateAccount(button, account_id) {
    $.ajax({
        'url': '/accounts/activate/' + account_id,
        'type': 'post',
        'success': function() {
            $(button).closest('tr').find('button.fa-ban').show();
            $(button).closest('tr').find('button.fa-play-circle').hide();
            toastr.clear();
            toastr.success('Account #' + account_id + ' was successfully reactivated', 'Success');
        },
        'error': function(response) {
			var errorText = '';
			for(var key in response.responseJSON) {
				errorText += response.responseJSON[key][0] + '</br>';
			}
			toastr.clear();
			toastr.error(errorText, 'Errors', {'timeOut' : '0', 'extendedTImeout': '0'});
        }
    });
}

function deactivateAccount(button, account_id) {
    $.ajax({
        'url': '/accounts/deactivate/' + account_id,
        'type': 'post',
        'success': function() {
            $(button).closest('tr').find('button.fa-ban').hide();
            $(button).closest('tr').find('button.fa-play-circle').show();
            toastr.clear();
            toastr.success('Account #' + account_id + ' was successfully deactivated', 'Success');
        },
        'error': function(response) {
			var errorText = '';
			for(var key in response.responseJSON) {
				errorText += response.responseJSON[key][0] + '</br>';
			}
			toastr.clear();
			toastr.error(errorText, 'Errors', {'timeOut' : '0', 'extendedTImeout': '0'});
        }
    });
}
