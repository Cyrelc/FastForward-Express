@extends ('layouts.tables')

@section ('variables')

<?php
	$contents = $contents->bills;
	$columns = ['ID', 'Waybill', 'Date', 'Description', 'Account', 'Amount', 'Pickup Driver', 'Delivery Driver', 'Pickup Address ID'];
	$variables = [['bill','bill_id'], ['bill','bill_number'], ['bill', 'date'], ['bill', 'description'], ['account', 'name'], ['bill', 'amount'],'pickup_driver_name', 'delivery_driver_name', ['bill','pickup_address_id']];
	$tableConfig = [
		'table' => 'bills',
		'editPath' => 'bills/edit/',
		'actionPath' => 'bills/action',
		'id_col' => 1,
		'name_col' => 3
	];
?>

	<script type="text/javascript">
		var columnDefs = [];
        var order = [1, "desc"];

		function dtRowCallback(row, data) {
		    var id = data[1];
		    var name = data[4].replace("'", "\\'");

            var editButton = '<a href="bills/edit/' + id + '"><i onclick="edit(this)" class="fa fa-edit"></i></a>';
    		var delButton = '<a href="javascript:action(' + id + ', \'' + name +'\', \'deactivate\')"><i class="fa fa-trash"></i></a>';
			var activateButton = '<a href="javascript:action(' + id + ', \'' + name +'\', \'activate\')"><i class="fa fa-toggle-on"></i></a>';

			if (data[1] == 0) {
			    $(row).addClass('disabled');
		        $(row).attr('title', 'Deactivated');
                $(row).find('.hover-div').html(editButton + activateButton);
			} else
				$(row).find('.hover-div').html(editButton + delButton);
		}
	</script>

@endsection

@section ('script')

@parent

@endsection
