@extends ('layouts.tables')

@section ('variables')

<?php
	$contents = $model->manifests;
	$columns = ['Id', 'Date Run', 'Driver', 'Bill Count'];//'First Bill Date', 'Last Bill Date', 'Amount'];
	$variables = [['manifest', 'manifest_id'], ['manifest', 'date_run'], ['driver_contact', 'first_name'], 'bill_count'];//, ['driver', 'name'], ['manifest', 'date'], 'bill_count'];//'first_bill_date', 'last_bill_date'];
	$tableConfig = [
		'table' => 'manifests',
		'editPath' => 'manifests/edit/',
		'actionPath' => 'manifests/action',
		'id_col' => 0
		];
?>

@endsection

@section ('script')
<script type="text/javascript">
	var columnDefs = [];
    var order = [2];

	function dtRowCallback(row, data) {
	    var id = data[1];

        var editButton = '<a class="btn btn-xs btn-default" href="manifests/view/' + id + '"><i class="fa fa-edit"></i></a>';
		var delButton = '<button type="button" class="fa fa-trash btn btn-xs btn-danger" data-toggle="modal" data-target="#delete_modal" onclick="setDeleteId(' + id + ')"></button>';

        $(row).find('.hover-div').html(editButton + delButton);
    }

	function setDeleteId(id){
		$("#delete_button").attr('href', '/manifests/delete/' + id);
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
				<h4 class="modal-title">Confirm Deletion of Manifest</h4>
			</div>
			<div class="modal-body">
				<p id="delete_message">Please confirm deletion of manifest. All affected bills will be marked as not yet manifested. This action can not be undone.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<a id="delete_button" type="button" class="btn btn-danger" href="/manifests/delete/&id&">Delete</a>
			</div>
		</div>
	</div>
</div>

@parent

@endsection

@section('navBar')

@endsection
