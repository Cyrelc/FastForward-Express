@extends ('layouts.app')

@yield ('variables')

@section ('script')

<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>

<script type='text/javascript'>
	var table;

	$(document).ready(function() {
	    table = $('#table').DataTable({
	        dom: 'lf<"columnVis"B>rtip',
	        buttons: [
	            'colvis'
	        ]
	    });

	$('#table tbody').on('click', 'td.details-control', function() {
		var tr = $(this).closest('tr');
		var rowClass = tr.class;
		var row = table.row(tr);
		if (row.child.isShown()) {
			row.child.hide();
			tr.removeClass('shown');
		} else {
			row.child(childRow()).show();
			tr.addClass('shown');
		}
	});
});

</script>

@endsection

@section ('style')

<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'>
 
@endsection

@section ('content')

<table id='table' class='display'>
	<thead class='header'>
		<tr>
			@foreach($columns as $column)
				<td>{{ $column }}</td>
			@endforeach
		</tr>
	</thead>

	<tbody>
		<tr>
			@foreach($columns as $column)
				<td class='details-control'>{{ $column }} goes here </td>
			@endforeach
		</tr>
	</tbody>
</table>

@endsection
