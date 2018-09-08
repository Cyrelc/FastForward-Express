@extends ('layouts.app')

@section('script')
<script type='text/javascript' src='{{URL::to('/')}}/js/admin/admin.js?{{config('view.version')}}'></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/validation.js'></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
@endsection

@section ('style')
<link rel="stylesheet" type="text/css" href="{{URL::to('/')}}/css/bootstrap-combobox.css" />
<style type="text/css">
#errors {
    color: red;
}

.split-50 {
    width: 50%;
    float: left;
}
</style>

@endsection

@section ('content')
<ul class='col-lg-1 nav nav-pills nav-stacked'>
	<li class="active"><a data-toggle="tab" href="#accounting">Accounting</a></li>
	<li><a data-toggle="tab" href="#categories">Categories</a></li>
    <li><a data-toggle="tab" href="#manage-users">Manage Users</a></li>
</ul>

<div class="col-lg-11 tab-content">
    <div id="accounting" class="tab-pane fade in active">
        <h3> Taxes </h3>
        <hr>
        <form id="gst_form" >
            <div class="col-lg-4 bottom15">
                <div class="input-group">
                    <span class="input-group-addon">GST : </span>
                    <input id="gst_percent" name="gst_percent" type="number" step="0.1" class="form-control" value="{{$model->GST}}" />
                    <span class="input-group-addon">%</span>
                </div>
            </div>
            <div class="col-lg-1 bottom15">
                <div class='text-left'>
                    <button type='button' class='btn btn-primary' onclick='storeGST()'>Submit</button>
                </div>
            </div>
        </form>
        </br>
        </br>
        <h3> Additional Charges </h3>
        <hr>
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Fuel Surcharge : </span>
                <input id="fuel_surcharge" name="fuel_surcharge" type="number" step="0.01" class="form-control" value="" />
                <span class="input-group-addon">%</span>
            </div>
        </div>
    </div>
    <div id="categories" class="tab-pane fade">
        <h3> CATEGORIES STUFF GOES HERE <h3>
        <p> This should be NOTICEABLY different from the other tab </p>
    </div>
    <div id="manage-users" class="tab-pane fade">
        @include('admin.userManagement')
    </div>
</div>
@endsection
