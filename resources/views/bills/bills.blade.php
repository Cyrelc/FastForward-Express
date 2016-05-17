@extends ('layouts.app')

@section ('script')

<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>

<script type='text/javascript'>
	var table;

	$(document).ready(function(){
		table = $('#billsTable').DataTable();

	})

</script>

@endsection

@section ('style')

<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.css'>

@endsection

@section ('navBar')

<table>
	<button class='navButton'>Create New Bill</button>
	<button class='navButton'>Edit Bill</button>
	<button class='navButton'>Eat IceCream</button>
</table>

@endsection

@section ('content')

<table id='billsTable' class='display'>
	<thead class="header">
		<tr>
			<th>Number</th>
			<th>Date</th>
			<th>Description</th>
			<th>Customer</th>
			<th>Amount</th>
			<th>Taxes</th>
			<th>Driver</th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td>A number goes here</td>
			<td>A date goes here</td>
			<td>Description goes here</td>
			<td>Customer goes here</td>
			<td>Amount goes here</td>
			<td>Taxes go here</td>
			<td>Driver goes here</td>
		</tr>
	</tbody>
</table>

@endsection
