@extends ('layouts.app')

<?php 
	$columns = array('Number', 'Date', 'Description', 'Customer', 'Amount', 'Taxes', 'Driver');
?>

@section ('script')

<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>

<script type='text/javascript'>
	var table;

	$(document).ready(function(){
	    table = $('#billsTable').DataTable( {
	        dom: 'lf<"columnVis"B>rtip',
	        buttons: [
	            'colvis'
	        ]
	    } );
	});

</script>

@endsection

@section ('style')

<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'>

@endsection

@section ('navBar')

<table>
	<button class='navButton btn-primary fa'><i class='fa-icon-plus'></i>Create New Bill</button>
	<button class='navButton btn-primary'>Edit Bill</button>
	<button class='navButton btn-primary'>Eat IceCream</button>
</table>

@endsection

@section ('content')

<table id='billsTable' class='display'>
	<thead class="header">
		<tr>
			@foreach($columns as $column)
				<td>{{$column}}</td>
			@endforeach
		</tr>
	</thead>

	<tbody>
		<tr>
			@foreach($columns as $column)
				<td>{{$column}} goes here </td>
			@endforeach
		</tr>
	</tbody>
</table>

@endsection
