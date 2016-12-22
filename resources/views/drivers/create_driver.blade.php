@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<script type='text/javascript' src='/js/create_driver.js'></script>

<script type="text/javascript">
	
// function validateForm() {

// }

</script>

@parent

@endsection

@section ('style')

@endsection

@section ('content')
<h2>New Driver</h2>
<form onsubmit="" method="POST" action="/drivers/store">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
	<div class="well form-group">
	<!-- errors will be output here -->
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
								<input type="text" name='first_name' class='form-control' placeholder='First Name' />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type='text' name="last_name" class='form-control' placeholder='Last Name' />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="email_address" class="form-control" placeholder="Email Address" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="email_address2" class="form-control" placeholder="Secondary Email Address" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="primary_phone" class="form-control" placeholder="Primary Phone Number" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="secondary_phone" class="form-control" placeholder="Secondary Phone Number" />
							</div>
							<div class="col-lg-6 clearfix">
								<input type="text" name="pager_number" class="form-control" placeholder="Pager Number" />
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
								<input type="text" name="address1" class="form-control" placeholder="Address 1" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="address2" class="form-control" placeholder="Address 2" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="postal_code" class="form-control" placeholder="Postal Code" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="city" class="form-control" placeholder="City" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="province" class="form-control" placeholder="Province" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="country" class="form-control" placeholder="Country" />
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
								<input type="text" name="emerg_first_name" class="form-control" placeholder="First Name" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_last_name" class="form-control" placeholder="Last Name" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_email_address" class="form-control" placeholder="Email Address" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_email_address2" class="form-control" placeholder="Secondary Email Address" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_primary_phone" class="form-control" placeholder="Primary Phone Number" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_secondary_phone" class="form-control" placeholder="Secondary Phone Number" />
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
								<input type="text" name="emerg_address1" class="form-control" placeholder="Address 1" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_address2" class="form-control" placeholder="Address 2" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_postal_code" class="form-control" placeholder="Postal Code" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_city" class="form-control" placeholder="City" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_province" class="form-control" placeholder="Province" />
							</div>
							<div class="col-lg-6 clearfix bottom15">
								<input type="text" name="emerg_country" class="form-control" placeholder="Country" />
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
								<input type="text" name="DLN" class="form-control" placeholder="Drivers License Number" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<input type="text" name="license_plate" class="form-control" placeholder="License Plate" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<input type="text" name="insurance" class="form-control" placeholder="Insurance Number" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<div id="license_expiration"></div>
<!-- 								<input type="text" name="license_expiration" class="form-control" placeholder="Drivers License Expiration" />
 -->							</div>
							<div class="col-lg-4 clearfix bottom15">
								<input type="text" name="license_plate_expiration" class="form-control" placeholder="License Plate Expiration Date" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<input type="text" name="insurance_expiration" class="form-control" placeholder="Insurance Expiration Date" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<input type="text" name="SIN" class="form-control" placeholder="SIN" />
							</div>
							<div class="col-lg-4 clearfix bottom15">
								<input type='text' name="DOB" class="form-control" placeholder="Date of Birth">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
@endsection

@section ('navBar')

@endsection
