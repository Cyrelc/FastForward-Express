@extends ('layouts.tables')

@section ('variables')

<?php
	$contents = $contents->invoices;
	$columns = ['Id', 'Account', 'Date', 'Bill Count'];//'First Bill Date', 'Last Bill Date', 'Number of Bills', 'Price', 'Tax', 'Total'];
	$variables = [['invoice', 'invoice_id'], ['account', 'name'], ['invoice', 'date'], 'bill_count'];//'first_bill_date', 'last_bill_date', 'bill_count'];
	$tableConfig = [
		'table' => 'invoices',
		'editPath' => 'invoices/edit/',
		'actionPath' => 'invoices/action',
		'id_col' => 1
		];
?>

	<script type="text/javascript">
		var columnDefs = [];
        var order = [1];

		function dtRowCallback(row, data) {
		    var id = data[1];
		    var name = data[4].replace("'", "\\'");

            var editButton = '<a href="invoices/edit/' + id + '"><i onclick="edit(this)" class="fa fa-edit"></i></a>';
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

@section('navBar')

@endsection
