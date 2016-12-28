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
	        ],
	        'order': [0, 'asc'],
	        'pageLength' : 50
	    });

	$('#table tbody').on('click', 'td.details-control', function() {
		var tr = $(this).closest('tr');
		var rowClass = tr.class;
		var row = table.row(tr);
		var numCols = $('#table').dataTable().fnSettings().aoColumns.length;
		if (row.child.isShown()) {
			row.child.hide();
			tr.removeClass('shown');
		} else {
			row.child(childRow(row.data()[numCols-1])).show();
			tr.addClass('shown');
		}
	});
});

</script>

<script type="text/javascript">
	function edit(className){
		$(className).prop('readonly', false);
		$(className + '.save-button').removeClass('hidden');
	}

</script>

@endsection

@section ('style')

<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'>

<link rel='stylesheet' type='text/css' href='/css/tables.css' />
 
@endsection

@section ('content')
<div class='right25'>
	<table id='table' >
		<thead class='header'>
			<tr>
				@foreach($columns as $column)
					<td>{{ $column }}</td>
				@endforeach
					<td class='hidden'></td>
			</tr>
		</thead>

		<tbody>
			@foreach($contents as $content)
				<tr>
	 			@foreach($variables as $variable)
					<td class='details-control'>{{$content->$variable}}</td>
				@endforeach
					<td class='hidden'>{{json_encode($content)}}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
</div>
@endsection
