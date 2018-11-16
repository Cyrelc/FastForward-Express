@extends('layouts.app')

@section('script')
<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/media/js/dataTables.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.print.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js'></script>
<script type='text/javascript' src='/js/employees/employees.js?{{config('view.version')}}'></script>
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
                <td>Employee ID</td>
                <td>Employee Number</td>
                <td>Employee Name</td>
                {{-- <td>Roles</td> --}}
                <td>Primary Phone</td>
                <td>Company Name</td>
			</tr>
		</thead>
	</table>
</div>
@endsection
