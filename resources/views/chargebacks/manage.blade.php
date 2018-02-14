@extends ('layouts.app')

@section ('script')
<script type="text/javascript" src="/js/bootstrap-combobox.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript' src='/js/toastr.min.js'></script>
<script type='text/javascript' src='/js/chargebacks/chargebacks.js'></script>
@parent
@endsection

@section('style')
<link rel="stylesheet" type="text/css" href="/css/bootstrap-combobox.css" />
<link rel='stylesheet' type='text/css' href='/css/toastr.min.css' />
@parent
@endsection

@section ('content')
<h2>Chargebacks</h2>

<ul class='nav nav-tabs'>
    <li class='active'><a data-toggle='tab' href='#new'>New Chargeback</a></li>
    <li><a data-toggle='tab' href='#manage' onclick='updateChargebacksList()'>Manage Chargebacks</a></li>
</ul>
<form id='chargeback_create_form'>
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div class='tab-content clearfix well'>
        <div id='new' class='tab-pane fade in active'>
            <div class='col-md-3 bottom15'>
                <div class='input-group'>
                    <span class='input-group-addon'>Name</span>
                    <input class='form-control' type='text' name='name' />
                </div>
            </div>
            <div class='col-md-3 bottom15'>
                <div class='input-group'>
                    <input class='form-control' type='number' name='charge_count' id='charge_count' value='1' min='1'/>
                    <span class='input-group-addon'>time(s) - or -</span>
                    <label class='input-group-addon'>Continuous <input type='checkbox' id='continuous' name='continuous'></label>
                </div>
            </div>
            <div class='col-md-3 bottom15'>
                <div class='input-group'>
                    <span class='input-group-addon'>Amount</span>
                    <input class='form-control' type='number' step='0.01' name='amount' />
                </div>
            </div>
            <div class='col-md-3 bottom15'>
                <div class='input-group'>
                    <span class='input-group-addon'>GL Code:</span>
                    <input class='form-control' type='text' name='gl_code' placeholder='(optional)' />
                </div>
            </div>
            <div class="col-lg-4 bottom15">
                <h4>Start Date</h4>
                <div class="input-group">
                    <input type='text' id="start_date" class="form-control" name='start_date' value="{{date("l, F d Y", $model->date)}}"/>
                    <span class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </span>
                </div>
            </div>
            <div class='col-md-4 bottom15'>
                <h4>Description</h4>
                <textarea class='form-control' name='description' placeholder='(optional)'></textarea>
            </div>
            <div class='col-md-4 bottom15'>
                <h4>Select Employees</h4>
                <select class='form-control' multiple name='employees[]'>
                    @foreach($model->employees as $employee)
                        <option value='{{$employee->employee_id}}'>{{$employee->employee_id}} - {{$employee->contact->first_name}} {{$employee->contact->last_name}}</option>
                    @endforeach
                </select>
            </div>
            <div class='col-md-12' style='text-align: center'>
                <button type='button' class='btn btn-primary' onclick='submitChargeback()'>Submit</button>
            </div>
        </form>
    </div>
    <div id='manage' class='tab-pane fade in'>
    </div>
</div>
@endsection

@section ('advFilter')
@endsection
