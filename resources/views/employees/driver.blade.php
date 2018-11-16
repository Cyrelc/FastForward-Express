<input type="hidden" name="driver_id" value="{{$model->driver->driver_id}}"/>

<div class="col-lg-12">
    <div class="panel panel-default">
        <div class='panel-heading'>
            <h3 class='panel-title'>Driver Information</h3>
        </div>
        <div class='panel-body'>
            <div class='col-md-12 bottom15'>
                <div class='input-group'>
                    <span class='input-group-addon'>Company Name: </span>
                    <input type='text' class='form-control' name='company_name' placeholder='(optional)' value='{{$model->driver->company_name}}'/>
                </div>
            </div>
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
            <div class="col-lg-6 well bottom15">
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
