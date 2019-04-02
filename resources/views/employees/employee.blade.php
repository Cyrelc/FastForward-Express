@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="{{URL::to('/')}}/js/moment.min.js"></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/employees/employee.js?{{config('view.version')}}"></script>
<script type='text/javascript' src='/js/partials/address.js?{{config('view.version')}}'></script>
@parent

@endsection

@section ('style')
<link rel="stylesheet" type="text/css" href="{{URL::to('/')}}/css/bootstrap-datetimepicker.min.css" />
@endsection

@section ('content')

@if(isset($model->employee->employee_id))
	<h2>Edit Employee</h2>
@else
	<h2>New Employee</h2>
@endif

	<input type="hidden" name="_token" value="{{ csrf_token() }}" />

	@if(isset($model) && isset($model->employee) && $model->employee->employee_id > 0)
		<input type="hidden" id="employee_id" name="employee_id" value="{{$model->employee->employee_id}}"/>
		<input type="hidden" name="user_id" value="{{$model->employee->user_id}}"/>
	@endif
	<div class="col-lg-12">
		<ul class='nav nav-tabs'>
			<li class="active"><a data-toggle="tab" href="#basic"><h4>Main</h4></a></li>
			<li id="driver_form_tab" name="driver_form_button" style="{{isset($model->driver->driver_id) ? '' : 'display:none'}}"><a data-toggle="tab" href="#driver"><h4>Driver</h4></a></li>
			<li id="sales_form_tab" class="hidden"><a data-toggle="tab" href="#sales"></h4>Sales</h4></a></li>
			<li id='admin_form_tab'><a data-toggle='tab' href='#admin'><h4>Administration</h4></a></li>
		</ul>
	</div>
	<div class="tab-content">
		<div id="basic" class="tab-pane fade in active">
			<form id='employee_contact_form'>
				@include('employees.basic');
			</form>
		</div>
		<div id='driver' class='tab-pane fade'>
			<form id='employee_driver_form'>
				@include('employees.driver');
			</form>
		</div>
		<div id='admin' class='tab-pane fade'>
			<form id='employee_admin_form'>
				@include('employees.admin');
			</form>
		</div>
	</div>
	<div class='text-center'>
		<button type='button' class='btn btn-primary' onclick='storeEmployee(this)'>Submit</button>
	</div>
@endsection

@section ('advFilter')
@endsection

