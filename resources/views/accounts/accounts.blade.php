@extends ('layouts.tables')

@section ('variables')

	<?php
		$contents = $contents->accounts;
		$columns = ['Active', 'ID', 'Parent ID', 'Name', 'Address'];//, 'Invoice Interval', 'Start Date'];
		$variables = [['account', 'active'], ['account', 'account_id'], ['account', 'parent_account_id'], ['account', 'name'], 'address'];//, 'id', 'name', 'address','contact_name'];
		$tableConfig = [
			'table' => 'account',
			'editPath' => 'accounts/edit/',
			'actionPath' => 'accounts/action',
			'id_col' => 1,
			'name_col' => 3
		];

	?>

	<script type="text/javascript">
		var columnDefs = [{"targets": [ 1 ], "visible": false, "searchable": true}];
        var order = [1, "desc"];

		function dtRowCallback(row, data) {
		    var id = data[2];
		    var name = data[4].replace("'", "\\'");

            var editButton = '<a href="accounts/edit/' + id + '"><i onclick="edit(this)" class="fa fa-edit"></i></a>';
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

<script type='text/javascript'>
	/*
	function childRow(details) {
		var data = JSON.parse(details);
debugger;
		var thisCust = 'editCust' + data.account.account_id;

		return "<div class='well'>" +
					"<form>" +
						"<div class='form-group'>" +
								"<button class='btn btn-primary' style='margin-right: 10px;'><i class='fa fa-save'></i> Save</button>" +
								"<button class='btn btn-danger'><i class='fa fa-trash'></i> Delete</button>" +
						"</div>" +
						"<div class='form-group'>" +
							"<label>Customer #  " + data.account.account_id + "</label><br />" +
							"<label>Full Name<br />" + "<input class='form-control' readonly value=" + data.account.name + " />" +
						"</div>" +
						"<div class='form-group'>" +
							"<div class='col-lg-6 clearfix'>" +
								"<input type='text' class='form-control' name='delivery-street' placeholder='Address Line 1' value='" + data.account.name + "'>" +
							"</div>" +
							"<div class='col-lg-6 clearfix bottom15'>" +
								"<input type='text' id='delivery-zip' class='form-control' name='delivery-zip-postal' placeholder='Postal/Zip Code' value=''>" +
							"</div>" +
							"<div class='col-lg-6 clearfix bottom15'>" +
								"<input type='text' class='form-control' name='delivery-street2' placeholder='Address Line 2' value=''>" +
							"</div>" +
							"<div class='col-lg-6 clearfix bottom15'>" +
								"<input type='text' class='form-control' name='delivery-state-province' placeholder='Province/State' value=''>" +
							"</div>" +
							"<div class='col-lg-6 clearfix'>" +
								"<input type='text' class='form-control' name='delivery-city' placeholder='City' value=''>" +
							"</div>" +
							"<div class='col-lg-6 clearfix'>" +
								"<input type='text' class='form-control' name='delivery-country' placeholder='Country' value=''>" +
							"</div>" +
							//"<label>Address:  </label>" + "<input class='form-control' readonly value=" + data.address + " />" +
						"</div>" +
						"<div class='form-group'>" +
							"<td>" + "<label>Primary Contact</label>" + "</td>" +
							"<td>" + "<label>Name: " + "<br>" + "<input class='" + thisCust + "' readonly value=" + data.contact_name + " />" + "</td>" +
							"<td>" + "<label>Phone: " + "<br>" + "<input class='" + thisCust + "' readonly value=" + data.phone_nums + " />" + "</td>" +
							"<td>" + "<label>Email: " + "<br>" + "<input class='" + thisCust + "' readonly value=" + data.email + " />" + "</td>" +
						"</div>" +
						"<div class='form-group'>" +
							"<td>" + "<label>Rate Type</label>" + "<br>" + "<select class='" + thisCust + "' readonly value=" + data.rate_type_id + " />" + "</td>" +
							"<td>" + "<label>Invoice Interval</label>" +"<br>" + "<select class='" + thisCust +"' readonly value=" + data.invoice_interval_id + "/>" + "</td>" +
							"<td></td>" +
							"<td></td>" +
						"</div>" +
					"</form>" +
				"</div>";
	}*/

</script>

@parent

@endsection
