@extends('layouts.app')

@section('script')
<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>
<script type='text/javascript' src='/js/manifests/manifests.js'></script>
<script type="text/javascript" src='/js/toastr.min.js'> </script>
@endsection

@section('style')
<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'/>
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
				<td>Manifest ID</td>
				<td>Driver ID</td>
				<td>Employee ID</td>
				<td>Driver</td>
				<td>Date Run</td>
				<td>First Bill Date</td>
				<td>Last Bill Date</td>
				<td>Bill Count</td>
			</tr>
		</thead>
	</table>
</div>
@endsection

@section('advFilter')
@endsection
