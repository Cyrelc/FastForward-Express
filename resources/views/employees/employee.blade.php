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

<form id='employee-form'>
	<input type="hidden" name="_token" value="{{ csrf_token() }}" />
	<input type="hidden" id="is_driver" name="is_driver" value="{{isset($model->driver->driver_id) ? "true" : "false" }}" />
	<input type="hidden" id="is_sales" name="is_sales" value="" />

	@if(isset($model) && isset($model->employee) && $model->employee->employee_id > 0)
		<input type="hidden" id="employee_id" name="employee_id" value="{{$model->employee->employee_id}}"/>
		<input type="hidden" name="user_id" value="{{$model->employee->user_id}}"/>
	@endif
	<div class="col-lg-12">
		<ul class='nav nav-pills'>
			<li class="active"><a data-toggle="pill" href="#basic"> Main </a></li>
			<li id="driver_form_button" name="driver_form_button" class="hidden"><a data-toggle="pill" href="#driver"> Driver</a></li>
			<li id="sales_form_button" class="hidden"><a data-toggle="pill" href="#sales"> Sales </a></li>
		</ul>
	</div>
	<div class="tab-content">
		<div id="basic" class="tab-pane fade in active">
			@include('employees.basic');
		</div>
		<div id='driver' class='tab-pane fade'>
			@include('employees.driver');
		</div>
	</div>
	<div class='text-center'>
		<button type='button' class='btn btn-primary' onclick='storeEmployee()'>Submit</button>
	</div>
</form>
@endsection

@section ('advFilter')
<div class="well form-group" id="job_types">
    <h4>Employee Roles</h4>
    <hr>
    <div class="checkbox">
        <label><input type="checkbox" id="is_driver_checkbox" {{$model->driver->driver_id == null ? "" : "checked" }} />Driver</label>
    </div>
    <div class="checkbox">
    	<label><input disabled type="checkbox" id="is_sales_checkbox" />Sales</label>
    </div>
</div>
@endsection
