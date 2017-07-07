@extends ('layouts.tables')

@section ('variables')

<?php
	$contents = $contents->bills;
	$columns = ['ID', 'Waybill', 'Date', 'Description', 'Account', 'Amount', 'Pickup Driver', 'Delivery Driver'];
	$variables = [['bill','bill_id'], ['bill','bill_number'], ['bill', 'date'], ['bill', 'description'], ['account', 'name'], ['bill', 'amount'],'pickup_driver_name', 'delivery_driver_name'];
	$tableConfig = [
		'table' => 'bills',
		'editPath' => 'bills/edit/',
		'actionPath' => 'bills/action',
		'id_col' => 1,
		'name_col' => 3
	];
?>

	<script type="text/javascript">
		// var columnDefs = [{"targets": [ 1, 2 ], "visible": false, "searchable": true}];
		var columnDefs = [];
        var order = [1, "desc"];

		function dtRowCallback(row, data) {
		    var id = data[2];
		    var name = data[4].replace("'", "\\'");

            var editButton = '<a href="bills/edit/' + id + '"><i onclick="edit(this)" class="fa fa-edit"></i></a>';
    		//var delButton = '<a href="javascript:action(' + id + ', \'' + name +'\', \'deactivate\')"><i class="fa fa-trash"></i></a>';
			// var activateButton = '<a href="javascript:action(' + id + ', \'' + name +'\', \'activate\')"><i class="fa fa-toggle-on"></i></a>';

		 //    if (data[1] == 0) {
		 //        $(row).addClass('disabled');
		 //        $(row).attr('title', 'Deactivated');
   //              $(row).find('.hover-div').html(editButton + activateButton);
			// } else
   //              $(row).find('.hover-div').html(editButton + delButton);
		}
	</script>

@endsection

@section ('script')

<script type='text/javascript'>

// 	function childRow(details) {
// 		var data = JSON.parse(details);

// 		var thisBill = 'editBill' + data.number;

// 		return "<table>" +
// 					"<tr>" +
// 						"<td>" +
// 							"<button class='edit-button' onclick=" + "edit('." + thisBill + "')" + "><i class='fa fa-edit'></i></button>" +
// 							"<button class='delete-button'><a href=''><i class='fa fa-trash'></i></a></button>" +
// 							"<button class='save-button hidden " + thisBill + "'><a href=''><i class='fa fa-save'></i></a></button>" +
// 						"</td>" +
// 						"<td></td>" +
// 						"<td></td>" +
// 						"<td></td>" +
// 						"<td>" + "<label>ID</label>" + "</td>" +
// 						"<td>" + "<label>%</label>" + "</td>" +
// 						"<td>" + "<label>Total</label>" + "</td>" +
// 					"</tr>" +
// 					"<tr>" +
// 						"<td>" + "<label>Bill #:</label>" + "<br>" + "<input class='' style='width: 100px;' readonly type='number' value='" + data.number + "' />" + "</td>" +
// 						"<td>" + "<label>Date:</label>" + "<br>" + "<input class='" + thisBill + "' style='width: 100px;' readonly type='" + data.date + "' />" + "</td>" +
// 						"<td>" + "<label>Amount:</label>" + "<br>" + "<input class='" + thisBill + "' style='width: 100px;' readonly type='number' step='0.01' value='" + data.amount + "' />" + "</td>" +
// 						"<td>" + "<label>Pickup:</label>" + "</td>" +
// /*driver pickup ID*/	"<td>" + "<input class='" + thisBill + "' style='width:65px;' readonly type='number'  min='1' value='" + data.driver_pickup_id + "' />" + "</td>" +
// /*driver pickup % */	"<td>" + "<input class='" + thisBill + "' style='width:50px;' readonly type='number' min='0' max='100' value='" + data.pickup_amount/data.driver_amount*100 + "' />" + "</td>" +
// /*driver pickup amount*/"<td>" + "<input class='' style='width:100px;' readonly type='number' step='0.01' value='" + data.pickup_amount + "' />" + "</td>" +
// 					"</tr>" +
// 					"<tr>" +
// 						"<td>" + "<label>Customer #:</label>" + "<br>" + "<input class='" + thisBill + "' style='width: 100px;' readonly type='number' value='" + data.customer_id + "' />" + "</td>" +
// 						"<td>" + "<label>Customer Name:</label>" + "<br>" + "<input class='" + thisBill + "' readonly type='text' value='' />" + "</td>" +
// 						"<td>" + "<label>Interliner Amount:</label>" + "<br>" + "<input class='" + thisBill + "' style='width: 100px;' readonly type='number' step='0.01' value='" + data.int_amount + "' />" + "</td>" +
// 						"<td>" + "<label>Delivery:</label>" + "</td>" +
// /*driver delivery ID*/	"<td>" + "<input class='" + thisBill + "' style='width:65px;' readonly type='number' min='1' value='" + data.driver_dropoff_id + "' />" + "</td>" +
// /*driver delivery % */	"<td>" + "<input class='" + thisBill + "' style='width:50px;' readonly type='number' min='0' max='100' value='" + data.dropoff_amount/data.driver_amount*100 + "' />" + "</td>" +
// /*driver delivery $$*/	"<td>" + "<input class='' style='width:100px;' readonly type='number' step='0.01' value='" + data.dropoff_amount + "' min='0.00' />" + "</td>" +
// 					"</tr>" +
// 					"<tr>" +
// 						"<td>" + "<label>Manifest: 15497</label>" + "</td>" +
// 						"<td>" + "<label>Invoice: 27648</label>" + "</td>" +
// 						"<td>" + "<label>Driver Amount:</label>" + "<br>" + "<input id='pants' class='" + thisBill + "' style='width: 100px;' readonly='true' type='money' value='" + data.driver_amount + "' />" + "</td>" +
// 						"<td>" + "<label>Interliner:</label>" + "</td>" +
// 						"<td></td>" +
// 						"<td></td>" +
// 						"<td></td>" +
// 					"</tr>" +
// 				"</table>" +
// 				"Description:" + data.description;
// 	}

</script>

@parent

@endsection
