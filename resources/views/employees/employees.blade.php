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

<!-- disable modal -->
<div id="password_change_modal" class="modal fade" role="dialog">
    <div class="modal-dialog">
<!-- disable modal content -->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id='password_change_title'>Change Password</h4>
            </div>
            <div class="modal-body">
                <p id="password_change_message">Please enter new password. <b>This action can not be undone.</b></p>
                <form id='password_change_form'>
                    <div class='input-group bottom15'>
                        <span class='input-group-addon'>Password:</span>
                        <input type='password' name='password' class='form-control' placeholder='New Password' />
                    </div>
                    <div class='input-group'>
                        <span class='input-group-addon'>Confirm Password:</span>
                        <input type='password' name='password_confirm' class='form-control' placeholder='Confirm Password' />
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button id="password_change_submit_button" type="button" class="btn btn-success">Submit</a>
            </div>
        </div>
    </div>
</div>
    
@endsection
