@extends('layouts.app')

@section('script')
<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/media/js/dataTables.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.print.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js'></script>
<script type='text/javascript' src='/js/bills/bills.js?{{config('view.version')}}'></script>
@endsection

@section('style')
<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.bootstrap.min.css'/>
<link rel="stylesheet" href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css' />
<link rel='stylesheet' type='text/css' href='/css/tables.css' />
@parent
@endsection

@section('content')
<div class='col-md-11'>
	<table id='table'>
		<thead>
			<tr>
				<td></td>
				<td>Bill ID</td>
				<td>Waybill</td>
				<td>Pickup Date</td>
				<td>Delivery Date</td>
				<td>Type</td>
				<td>Account #</td>
				<td>Account</td>
				<td>Pickup Driver</td>
				<td>Delivery Driver</td>
				<td>Interliner</td>
				<td>Description</td>
				<td>Invoice</td>
				<td>Pickup Manifest</td>
				<td>Delivery Manifest</td>
				<td>Amount</td>
				<td>Payment Type</td>
				<td>Complete</td>
			</tr>
		</thead>
	</table>
</div>

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
				<p id="delete_message">Please confirm deletion of bill. <b>This action can not be undone.</b></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<a id="delete_button" type="button" class="btn btn-danger" href="">Delete</a>
			</div>
		</div>
	</div>
</div>
@endsection

@section('advFilter')
<form id='bills_advanced_filters'>
	<div class='well' style='text-align:center'>
		<div class='input-group'>
			<span class='input-group-addon'>Bill Filter</span>
			<select class='form-control selectpicker' name='bills_advanced_filter' id='bills_advanced_filter'>
				<option></option>
				<option value='dispatch'>Dispatch</option>
				<option value='billing'>Billing</option>
			</select>
		</div>
	</div>
</form>
@endsection
