@extends('layouts.app')

@section('script')
<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js'></script>
<script type='text/javascript' src='/js/bills/bills.js'></script>
<script type="text/javascript" src='/js/toastr.min.js'> </script>
@endsection

@section('style')
<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'/>
<link rel="stylesheet" href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css' />
<link rel='stylesheet' type='text/css' href='/css/tables.css' />
<link rel='stylesheet' type='text/css' href='/css/toastr.min.css' />
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
				<td>Date</td>
				<td>Type</td>
				<td>Account</td>
				<td>Pickup Driver</td>
				<td>Delivery Driver</td>
				<td>Interliner</td>
				<td>Description</td>
				<td>Packages</td>
				<td>Invoice</td>
				<td>Pickup Manifest</td>
				<td>Delivery Manifest</td>
				<td>Amount</td>
			</tr>
		</thead>
	</table>
</div>
@endsection

@section('advFilter')
<div class='well' style='text-align:center'>
</div>
@endsection
