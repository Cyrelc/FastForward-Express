@extends ('layouts.tables')

@section ('variables')

<?php
	$contents = $contents->invoices;
	$columns = ['Id', 'Account', 'Date Run', 'Bill Count'];//'First Bill Date', 'Last Bill Date', 'Number of Bills', 'Price', 'Tax', 'Total'];
	$variables = [['invoice', 'invoice_id'], ['account', 'name'], ['invoice', 'date'], 'bill_count'];//'first_bill_date', 'last_bill_date', 'bill_count'];
	$tableConfig = [
		'table' => 'invoices',
		'editPath' => 'invoices/edit/',
		'actionPath' => 'invoices/action',
		'id_col' => 1
		];
?>

@endsection

@section ('script')
<script type="text/javascript">
	var columnDefs = [];
    var order = [1];

	function dtRowCallback(row, data) {
	    var id = data[1];
	    var name = data[4].replace("'", "\\'");

        var editButton = '<a class="btn btn-xs btn-default" href="invoices/view/' + id + '"><i class="fa fa-edit"></i></a>';
		var delButton = '<button type="button" class="fa fa-trash btn btn-xs btn-danger" data-toggle="modal" data-target="#delete_modal" onclick="setDeleteId(' + id + ')"></button>';

		if (data[1] == 0) {
		    $(row).addClass('disabled');
	        $(row).attr('title', 'Deactivated');
            $(row).find('.hover-div').html(editButton + activateButton);
		} else
			$(row).find('.hover-div').html(editButton + delButton);
	}

	function setDeleteId(id){
		$("#delete_button").attr('href', '/invoices/delete/' + id);
	}
</script>

@parent

@endsection

@section ('content')
<!-- delete modal -->
<div id="delete_modal" class="modal fade" role="dialog">
	<div class="modal-dialog">
<!-- delete modal content -->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Confirm Deletion of Invoice</h4>
			</div>
			<div class="modal-body">
				<p id="delete_message">Please confirm deletion of invoice. This action can not be undone.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<a id="delete_button" type="button" class="btn btn-danger" href="/invoices/delete/&id&">Delete</a>
			</div>
		</div>
	</div>
</div>

@parent

@endsection

@section('navBar')

@endsection
