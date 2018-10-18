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
			<li class="active"><a data-toggle="pill" href="#main"> Main </a></li>
			<li id="driver_form_button" name="driver_form_button" class="hidden"><a data-toggle="pill" href="#driver"> Driver</a></li>
			<li id="sales_form_button" class="hidden"><a data-toggle="pill" href="#sales"> Sales </a></li>
		</ul>
	</div>
	<div class="tab-content">
		<div id="main" class="tab-pane fade in active">
<!--Contact Info Panel-->
				<div class="col-lg-12">
					<div class='panel panel-default'>
						<div class='panel-heading'>
							<h3 class='panel-title'>Contact Info</h3>
						</div>
						<div class='panel-body'>
							@include('partials.contact', ['prefix' => 'employee', 'show_address' => true, 'contact' => $model->contact])
						</div>
					</div>
				</div>

<!--Additional Info Panel-->
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Additional Information</h3>
					</div>

					<div class="panel-body">
<!--SIN-->
						<div class="col-lg-4 bottom15">
							<div class="input-group">
								<span class="input-group-addon">SIN</span>
								<input type="text" id="sin" name="SIN" class="form-control" placeholder="SIN" value="{{$model->employee->sin}}"/>
							</div>
						</div>

<!--DOB-->
						<div class="col-lg-4 bottom15">
							<div class='input-group date' id='dob-picker'>
								<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> Birth Date</span>
								<input type='text' name="DOB" class="form-control" placeholder="Date of Birth" value="{{date("l, F d Y", $model->employee->dob)}}"/>
							</div>
						</div>

<!--Start Date-->
						<div class="col-lg-4 bottom15">
							<div class='input-group date' id='startdate-picker'>
								<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> Start Date</span>
								<input type='text' name="startdate" class="form-control" placeholder="Start Date" value="{{date("l, F d Y", $model->employee->start_date)}}"/>
							</div>
						</div>
<!-- Employee Number -->
						<div class="col-lg-4 bottom15">
							<div class='input-group'>
								<span class="input-group-addon">Employee Number </span>
								<input type='text' name="employee_number" class='form-control' value="{{$model->employee->employee_number}}" />
							</div>
						</div>
					</div>
				</div>
			</div>
<!--Emergency Contacts-->
		@include('partials.contacts', ['contacts' => $model->emergency_contacts, 'show_address' => true, 'title' => 'Emergency Contacts', 'prefix' => 'emergency-contact'])
		</div>

		<div id='driver' class='tab-pane fade'>
			<input type="hidden" name="driver_id" value="{{$model->driver->driver_id}}"/>

			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class='panel-heading'>
						<h3 class='panel-title'>Driver Information</h3>
					</div>
					<div class='panel-body'>
<!--Driver's License-->
<!--DLN-->
						<div class="col-lg-6 well bottom15">
							<div class="input-group bottom15">
								<span class="input-group-addon"><i class="fa fa-id-card-o"></i> Drivers License Number</span>
								<input type="text" id="dln" name="DLN" class="form-control dln" placeholder="Drivers License Number" value="{{$model->driver->drivers_license_number}}"/>
							</div>
<!--License Expiration-->
							<div class='input-group' id='license-picker'>
								<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i>&nbsp&nbspExpiration Date</span>
								<input type='text' name="license_expiration" class="form-control" placeholder="Drivers License Expiration Date" value="{{date("l, F d Y", $model->driver->license_expiration)}}"/>
							</div>
						</div>
<!--Pickup Commission-->
						<div class="col-lg-6 bottom15">
							<div class="input-group bottom15">
								<span class='input-group-addon'>Pickup Commission</span>
								<input type="number" name="pickup-commission" class="form-control" placeholder="Pickup Commission" value="{{$model->driver->pickup_commission}}"/>
								<span class="input-group-addon">%</span>
							</div>
<!--Delivery Commission-->
							<div class="input-group">
								<span class='input-group-addon'>Delivery Commission</span>
								<input type="number" name="delivery-commission" class="form-control" placeholder="Delivery Commission" value="{{$model->driver->delivery_commission}}"/>
								<span class="input-group-addon">%</span>
							</div>
						</div>
					</div>
				</div>
				<div class="panel panel-default">
					<div class='panel-heading'>
						<h3 class='panel-title'> Vehicle Information </h3>
					</div>
					<div class='panel-body'>
<!--License Plate-->
						<div class="col-lg-6">
							<div class="well">
<!--License Plate-->
								<div class="input-group bottom15">
									<span class="input-group-addon"><i class="fa fa-car"></i>&nbsp&nbspLicense Plate</span>
									<input type="text" id="lp" name="license_plate" class="form-control" placeholder="License Plate" value="{{$model->driver->license_plate_number}}"/>
								</div>

<!--License Plate Expiration-->
								<div id="license_plate_expiration" class="bottom15">
									<div class="input-group" id="lp-picker">
										<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i>Expiration Date</span>
										<input type='text' name="license_plate_expiration" class="form-control" placeholder="License Plate Expiration Date" value="{{date("l, F d Y", $model->driver->license_plate_expiration)}}"/>
									</div>
								</div>
							</div>
						</div>

<!--Insurance-->
						<div class="col-lg-6">
							<div class="well">
<!--Insurance Number-->
								<div class="input-group bottom15">
									<span class="input-group-addon"><i class="fa fa-road"></i>&nbsp&nbspInsurance Number</span>
									<input type="text" name="insurance" class="form-control" placeholder="Insurance Number" value="{{$model->driver->insurance_number}}"/>
								</div>
<!--Insurance Expiration-->
								<div id="insurance_expiration" class="bottom15">
									<div class='input-group date' id='insurance-picker'>
										<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i>&nbsp&nbspExpiration Date</span>
										<input type='text' name="insurance_expiration" class="form-control" placeholder="Insurance Expiration Date" value="{{date("l, F d Y", $model->driver->insurance_expiration)}}"/>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
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
