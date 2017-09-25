@extends ('layouts.tables')

@section ('variables')

<?php
	$contents = $contents->bills;
	$columns = ['ID', 'Waybill', 'Date', 'Description', 'Account', 'Amount', 'Pickup Driver', 'Delivery Driver', 'Delivery Type', 'Package Count'];
	$variables = [['bill','bill_id'], ['bill','bill_number'], ['bill', 'date'], ['bill', 'description'], ['account', 'name'], ['bill', 'amount'],'pickup_driver_name', 'delivery_driver_name', ['bill','delivery_type'],['bill', 'num_pieces']];
	$tableConfig = [
		'table' => 'bills',
		'editPath' => 'bills/edit/',
		'actionPath' => 'bills/action',
		'id_col' => 1,
		'name_col' => 3
	];
?>

@endsection

@section ('script')
<script type="text/javascript">
	var columnDefs = [{"sWidth":"35px","aTargets":[0]}];
    var order = [1, "desc"];

	function dtRowCallback(row, data) {
	    var id = data[1];
	    var name = data[4].replace("'", "\\'");

        var editButton = '<a class="btn btn-xs btn-default" href="bills/edit/' + id + '"><i onclick="edit(this)" class="fa fa-edit"></i></a>';
		var delButton = '<button type="button" class="fa fa-trash btn btn-xs btn-danger" data-toggle="modal" data-target="#delete_modal" onclick="setDeleteId(' + id + ')"></button>';

		if (data[1] == 0) {
		    $(row).addClass('disabled');
	        $(row).attr('title', 'Deactivated');
            $(row).find('.hover-div').html(editButton + activateButton);
		} else
			$(row).find('.hover-div').html(editButton + delButton);
	}

	function setDeleteId(id){
		$("#delete_button").attr('href', '/bills/delete/' + id);
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
				<h4 class="modal-title">Confirm Deletion of Bill</h4>
			</div>
			<div class="modal-body">
				<p id="delete_message">Please confirm deletion of bill. This action can not be undone.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<a id="delete_button" type="button" class="btn btn-danger" href="/bills/delete/&id&">Delete</a>
			</div>
		</div>
	</div>
</div>

@parent

@endsection
