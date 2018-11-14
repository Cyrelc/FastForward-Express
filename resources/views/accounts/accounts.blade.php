@extends('layouts.app')

@section('script')
<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/media/js/dataTables.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.print.min.js'></script>
<script type='text/javascript' src='/js/accounts/accounts.js?{{config('view.version')}}'></script>
@endsection

@section('style')
<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.bootstrap.min.css'/>
<link rel='stylesheet' type='text/css' href='/css/tables.css' />
@parent
@endsection

@section('content')
<div class='col-md-11'>
	<table id='table'>
		<thead>
			<tr>
				<td></td>
				<td>Account ID</td>
				<td>Account Number</td>
				<td>Parent Account</td>
				<td>Account Name</td>
				<td>Invoice Interval</td>
				<td>Contact</td>
				<td>Shipping Address Name</td>
				<td>Shipping Address</td>
				<td>Billing Address Name</td>
				<td>Billing Address</td>
			</tr>
		</thead>
	</table>
</div>
@endsection

@section('advFilter')
@endsection
