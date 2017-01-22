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
		phoneInput('ppn');
		phoneInput('spn');
		phoneInput('pager-pn');
		phoneInput('epn');
		phoneInput('espn');
		zipInput('zip');
		zipInput('ezip');

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
	<div class="well form-group">
	<!-- errors will be output here -->
		@if(!empty($errors) && $errors->count() > 0)
			<div class="row">
				<div class="col-lg-12">
					<div class="alert alert-danger">
						<p>The following errors occurred on submit:</p>

						<ul>
							@foreach($errors->all() as $message)
								<li>{{  $message }}</li>
							@endforeach
						</ul>
					</div>
				</div>
			</div>
		@endif
		<p id='errors'></p>
		<div class="row row-eq-height">
			<div class="col-lg-6 clearfix">
				<div class='panel panel-default'>
					<div class='panel-heading'>
						<h3 class='panel-title'>Contact Info</h3>
					</div>
					<div class='panel-body'>
						<div class='row'>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name='first_name' class='form-control' placeholder='First Name' value="{{old('first_name')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type='text' name="last_name" class='form-control' placeholder='Last Name' value="{{old('last_name')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="email_address" class="form-control" placeholder="Email Address" value="{{old('email_address')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="email_address2" class="form-control" placeholder="Secondary Email Address" value="{{old('email_address2')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input id="ppn" type="text" name="primary_phone" class="form-control" placeholder="Primary Phone Number" value="{{old('primary_phone')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input id="spn" type="text" name="secondary_phone" class="form-control" placeholder="Secondary Phone Number" value="{{old('secondary_phone')}}" />
							</div>
							<div class="col-lg-6 clearfix">
								<input id="pager-pn" type="text" name="pager_number" class="form-control" placeholder="Pager Number" value="{{old('pager_number')}}" />
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-6 clearfix">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class='panel-title'>Home Address</h3>
					</div>
					<div class="panel-body">
						<div class="row">
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="address1" class="form-control" placeholder="Address 1" value="{{old('address1')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="address2" class="form-control" placeholder="Address 2" value="{{old('address2')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input id="zip" type="text" name="postal_code" class="form-control" placeholder="Postal Code" value="{{old('postal_code')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="city" class="form-control" placeholder="City" value="{{old('city')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="province" class="form-control" placeholder="Province" value="{{old('province')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="country" class="form-control" placeholder="Country" value="{{old('country')}}" />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-6 clearfix">
				<div class="panel panel-default">
					<div class='panel-heading'>
						<h3 class='panel-title'>Emergency Contact</h3>
					</div>
					<div class='panel-body'>
						<div class='row'>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_first_name" class="form-control" placeholder="First Name" value="{{old('emerg_first_name')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_last_name" class="form-control" placeholder="Last Name" value="{{old('emerg_last_name')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_email_address" class="form-control" placeholder="Email Address" value="{{old('emerg_email_address')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_email_address2" class="form-control" placeholder="Secondary Email Address" value="{{old('emerg_email_address2')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input id="epn" type="text" name="emerg_primary_phone" class="form-control" placeholder="Primary Phone Number" value="{{old('emerg_primary_phone')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input id="espn" type="text" name="emerg_secondary_phone" class="form-control" placeholder="Secondary Phone Number" value="{{old('emerg_secondary_phone')}}" />
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-6 clearfix">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Emergency Contact Address</h3>
					</div>
					<div class="panel-body">
						<div class="row">
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_address1" class="form-control" placeholder="Address 1" value="{{old('emerg_address1')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_address2" class="form-control" placeholder="Address 2" value="{{old('emerg_address2')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input id="ezip" type="text" name="emerg_postal_code" class="form-control" placeholder="Postal Code" value="{{old('emerg_postal_code')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_city" class="form-control" placeholder="City" value="{{old('emerg_city')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_province" class="form-control" placeholder="Province" value="{{old('emerg_province')}}" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_country" class="form-control" placeholder="Country" value="{{old('emerg_country')}}" />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12 clearfix">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Additional Information</h3>
					</div>
					<div class="panel-body">
						<div class="row">
							<div class="col-lg-4 clearfix bottom15">
								<input type="text" id="dln" name="DLN" class="form-control dln" placeholder="Drivers License Number" value="{{old('DLN')}}" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<input type="text" id="lp" name="license_plate" class="form-control" placeholder="License Plate" value="{{old('license_plate')}}" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<input type="text" name="insurance" class="form-control" placeholder="Insurance Number" value="{{old('insurance')}}" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<div id="license_expiration">
									<div class='input-group date' id='license-picker'>
										<input type='text' name="license_expiration" class="form-control" placeholder="Drivers License Expiration Date" value="{{old('license_expiration')}}"/>
										<span class="input-group-addon">
											<span class="glyphicon glyphicon-calendar"></span>
										</span>
									</div>
								</div>
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<div class='input-group date' id='lp-picker'>
									<input type='text' name="license_plate_expiration" class="form-control" placeholder="License Plate Expiration Date" value="{{old('license_plate_expiration')}}"/>
									<span class="input-group-addon">
                        				<span class="glyphicon glyphicon-calendar"></span>
                    				</span>
								</div>
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<div class='input-group date' id='insurance-picker'>
									<input type='text' name="insurance_expiration" class="form-control" placeholder="Insurance Expiration Date" value="{{old('insurance_expiration')}}"/>
									<span class="input-group-addon">
                        				<span class="glyphicon glyphicon-calendar"></span>
                    				</span>
								</div>
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<input type="text" id="sin" name="SIN" class="form-control" placeholder="SIN" value="{{old('SIN')}}" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<div class='input-group date' id='dob-picker'>
									<input type='text' name="DOB" class="form-control" placeholder="Date of Birth" value="{{old('DOB')}}"/>
									<span class="input-group-addon">
                        				<span class="glyphicon glyphicon-calendar"></span>
                    				</span>
								</div>
							</div>
						</div>

						<div class="row">
							<hr />
							<div class="col-lg-4 clearfix bottom15">
								<div class='input-group date' id='startdate-picker'>
									<input type='text' name="startdate" class="form-control" placeholder="Start Date" value="{{old('DOB')}}"/>
									<span class="input-group-addon">
                        				<span class="glyphicon glyphicon-calendar"></span>
                    				</span>
								</div>
							</div>
							<div class="col-lg-4">
								<label class="checkbox" style="padding-left: 23px;">
									<input id='chkActive' type='checkbox' name="active" checked> Active
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class='text-center'><button type='submit' class='btn btn-primary'>Submit</button></div>
</form>
@endsection

@section ('navBar')

@endsection
