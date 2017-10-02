@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="{{URL::to('/')}}/js/moment.min.js"></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
        dateInput('license-picker');
        dateInput('lp-picker');
        dateInput('insurance-picker');
        dateInput('dob-picker');
        dateInput('startdate-picker');
        phoneInput('pager');

		$("#dln").keydown(function(e){numberFilter(e);});
		$("#sin").keydown(function(e){numberFilter(e);});

        new Cleave('#dln', {
            delimiter: '-',
			blocks: [6, 3]
		});

        new Cleave('#sin', {
            delimiter: ' ',
            blocks: [3, 3, 3]
        });

		@if(!empty($errors) && $errors->count() > 0)
			var active = '{{old('active')}}';
			if (active == '') {
				$("#chkActive").attr('checked', false);
			}
		@endif
	});

</script>

@parent

@endsection

@section ('style')

	<link rel="stylesheet" type="text/css" href="{{URL::to('/')}}/css/bootstrap-datetimepicker.min.css" />

@endsection

@section ('content')
<h2>New Driver</h2>
<form onsubmit="" method="POST" action="/drivers/store">
	<input type="hidden" name="_token" value="{{ csrf_token() }}">

	@if(isset($model) && isset($model->driver) && $model->driver->driver_id > 0)
		<input type="hidden" name="driver-id" value="{{$model->driver->driver_id}}"/>
		<input type="hidden" name="user-id" value="{{$model->driver->user_id}}"/>
	@endif

	<div class="well">
		<div class="clearfix">
			<!-- errors will be output here -->
			@if(!empty($errors) && $errors->count() > 0)
				<div class="row">
					<div class="col-lg-12">
						<div class="alert alert-danger">
							<p>The following errors occurred on submit:</p>

							<ul>
								@foreach($errors->all() as $message)
									<!--Custom Messages-->
										@if ($message === "The contacts field is required.")
											<li>At least one emergency contact must be provided.</li>
										@elseif ($message === "The contact- action field is required.")
											<li>An error has occurred. Please contact us and provide the following message: <pre>Contact Action not submitted.</pre></li>
										@else
											<li>{{ $message }}</li>
										@endif
								@endforeach
							</ul>
						</div>
					</div>
				</div>
			@endif
			<p id='errors'></p>

			<!--Contact Info Panel-->
			<div class="col-lg-12">
				<div class='panel panel-default'>
					<div class='panel-heading'>
						<h3 class='panel-title'>Contact Info</h3>
					</div>
					<div class='panel-body'>
						@include('partials.contact', ['prefix' => 'contact', 'show_address' => true, 'contact' => $model->contact])
					</div>
				</div>
			</div>

			<!--Emergency Contacts-->
			@include('partials.contacts', ['contacts' => $model->emergency_contacts, 'show_address' => true, 'title' => 'Emergency Contacts', 'prefix' => 'sc'])

			<!--Additional Info Panel-->
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Additional Information</h3>
					</div>

					<div class="panel-body">

						<!--Driver's License-->
						<div class="col-lg-4">
							<div class="well">
								<!--DLN-->
								<div class="input-group bottom15">
									<span class="input-group-addon"><i class="fa fa-id-card-o"></i> Drivers License Number</span>
									<input type="text" id="dln" name="DLN" class="form-control dln" placeholder="Drivers License Number" value="{{$model->driver->drivers_license_number}}"/>
								</div>

								<!--License Expiration-->
								<div id="license_expiration" class="bottom15">
									<div class='input-group' id='license-picker'>
										<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
										<input type='text' name="license_expiration" class="form-control" placeholder="Drivers License Expiration Date" value="{{date("l, F d Y", $model->driver->license_expiration)}}"/>
									</div>
								</div>
							</div>
						</div>

						<!--License Plate-->
						<div class="col-lg-4">
							<div class="well">
								<!--License Plate-->
								<div class="input-group bottom15">
									<span class="input-group-addon"><i class="fa fa-car"></i>&nbsp&nbspLicense Plate</span>
									<input type="text" id="lp" name="license_plate" class="form-control" placeholder="License Plate" value="{{$model->driver->license_plate_number}}"/>
								</div>

								<!--License Plate Expiration-->
								<div id="license_plate_expiration" class="bottom15">
									<div class="input-group" id="lp-picker">
										<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
										<input type='text' name="license_plate_expiration" class="form-control" placeholder="License Plate Expiration Date" value="{{date("l, F d Y", $model->driver->license_plate_expiration)}}"/>
									</div>
								</div>
							</div>
						</div>

						<!--Insurance-->
						<div class="col-lg-4">
							<div class="well">
								<!--Insurance Number-->
								<div class="input-group bottom15">
									<span class="input-group-addon"><i class="fa fa-road"></i>&nbsp&nbspInsurance Number</span>
									<input type="text" name="insurance" class="form-control" placeholder="Insurance Number" value="{{$model->driver->insurance_number}}"/>
								</div>
								<div id="insurance_expiration" class="bottom15">
									<div class='input-group date' id='insurance-picker'>
										<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
										<input type='text' name="insurance_expiration" class="form-control" placeholder="Insurance Expiration Date" value="{{date("l, F d Y", $model->driver->insurance_expiration)}}"/>
									</div>
								</div>
							</div>
						</div>

						<!--SIN-->
						<div class="col-lg-4 bottom15">
							<div class="input-group">
								<span class="input-group-addon">SIN</span>
								<input type="text" id="sin" name="SIN" class="form-control" placeholder="SIN" value="{{$model->driver->sin}}"/>
							</div>
						</div>

						<!--DOB-->
						<div class="col-lg-4 bottom15">
							<div class='input-group date' id='dob-picker'>
								<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> Birth Date</span>
								<input type='text' name="DOB" class="form-control" placeholder="Date of Birth" value="{{date("l, F d Y", $model->driver->dob)}}"/>
							</div>
						</div>

						<!--Start Date-->
						<div class="col-lg-4 bottom15">
							<div class='input-group date' id='startdate-picker'>
								<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> Start Date</span>
								<input type='text' name="startdate" class="form-control" placeholder="Start Date" value="{{date("l, F d Y", $model->driver->start_date)}}"/>
							</div>
						</div>

						<!--Pickup Commission-->
						<div class="col-lg-4 bottom15">
							<div class="input-group">
								<input type="number" name="pickup-commission" class="form-control" placeholder="Pickup Commission" value="{{$model->driver->pickup_commission}}"/>
								<span class="input-group-addon">%</span>
							</div>
						</div>

						<!--Delivery Commission-->
						<div class="col-lg-4 bottom15">
							<div class="input-group">
								<input type="number" name="delivery-commission" class="form-control" placeholder="Delivery Commission" value="{{$model->driver->delivery_commission}}"/>
								<span class="input-group-addon">%</span>
							</div>
						</div>

						<!--Pager Number-->
						<div class="col-lg-5 bottom15">
							@include('partials.phone_number', ['prefix' => 'pager', 'phone' => $model->contact->pager, 'placeholder'=>'Pager', 'isPrimary'=>false])
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class='text-center'>
		<button type='submit' class='btn btn-primary'>Submit</button>
	</div>
</form>
@endsection

@section ('navBar')

@endsection
