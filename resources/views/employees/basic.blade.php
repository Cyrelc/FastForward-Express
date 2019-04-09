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
        </div>
    </div>
</div>
<!--Emergency Contacts-->
@if(isset($model->employee->employee_id))
    <div class='col-lg-12'>
        <div class='panel panel-default'>
            <div class='panel panel-heading'>
                <h3 class="panel-title">Emergency Contacts</h3>
            </div>
            <div class='panel-body'>
                <div class='col-lg-12'>
                    @include('employees.emergencyContacts')
                </div>
            </div>
        </div>
    </div>
@endif

