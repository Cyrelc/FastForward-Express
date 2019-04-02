<script type="text/javascript" src="/js/employees/admin.js?{{config('view.version')}}"></script>

<div class='col-lg-12'>
    <div class='panel panel-default'>
        <div class='panel-header'>
        </div>
        <div class='panel-body'>
            <input type='hidden' name='employee_id' value='{{$model->employee->employee_id}}' />
<!-- Employee Number -->
            <div class="col-lg-4 bottom15">
                <div class='input-group'>
                    <span class="input-group-addon">Employee Number </span>
                    <input type='text' name="employee_number" class='form-control' value="{{$model->employee->employee_number}}" />
                </div>
            </div>
            <div class='col-lg-4 bottom15'>
                <input type='checkbox' id='active' name='active' class='form-check-input checkbox-lg' />
                <label class='form-check-label' for='active'><h4>Active</h4></label>
            </div>
            <div class='col-md-4 bottom15'>
                <input type='checkbox' id='is_driver' name='is_driver' class='form-check-input checkbox-lg' {{$model->driver->driver_id == null ? "" : "checked" }} />
                <label class='form-check-label' for='is_driver'><h4>Driver</h4></label>
            </div>
            <div class='col-md-4 bottom15 hidden'>
                <input type='checkbox' id='is_sales' name='is_sales' class='form-check-input checkbox-lg' />
                <label class='form-check-label' for='is_sales'><h4>Sales</h4></label>
            </div>
            <p>SoMe PeRmIsSiOnS sTuFf WiLl Go HeRe</p>
        </div>
    </div>
</div>

