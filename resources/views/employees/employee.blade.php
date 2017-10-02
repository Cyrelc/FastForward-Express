@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="{{URL::to('/')}}/js/moment.min.js"></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
        dateInput('dob-picker');
        dateInput('startdate-picker');

		$("#sin").keydown(function(e){numberFilter(e);});

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

@if(isset($model->employee->employee_id))
	<h2>Edit Employee</h2>
@else
	<h2>New Employee</h2>
@endif

<form onsubmit="" method="POST" action="/employees/store">
	<input type="hidden" name="_token" value="{{ csrf_token() }}">

	@if(isset($model) && isset($model->employee) && $model->employee->employee_id > 0)
		<input type="hidden" name="employee_id" value="{{$model->employee->employee_id}}"/>
		<input type="hidden" name="user_id" value="{{$model->employee->user_id}}"/>
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
		</div>
	</div>
	<div class='text-center'>
		<button type='submit' class='btn btn-primary'>Submit</button>
	</div>
</form>
@endsection

@section ('navBar')

@endsection
