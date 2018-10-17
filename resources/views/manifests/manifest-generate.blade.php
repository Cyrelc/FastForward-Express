@extends ('layouts.app')

@section ('script')
<script type="text/javascript" src="/js/bootstrap-combobox.js"></script>
<script type='text/javascript' src='/js/manifests/manifest-generate.js?{{config('view.version')}}'></script>
@parent
@endsection

@section('style')
<link rel="stylesheet" type="text/css" href="/css/bootstrap-combobox.css" />
@parent
@endsection

@section('content')

<h2>Generate Manifests</h2>

<form id='manifest-form'>

    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="driver_count" id="driver_count">

	<div class="clearfix well">
<!-- start date -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Start Date: </span>
                <input type='text' id="start_date" onblur='getDriversToManifest()' class="form-control" name='start_date' value="{{date("F d, Y H:i:s", $model->start_date)}}" />
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>

<!-- end date -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">End Date: </span>
                <input type='text' id="end_date" onblur='getDriversToManifest()' class="form-control" name='end_date' value="{{date("F d, Y H:i:s", $model->end_date)}}" />
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>
<!-- preview list -->
        <script type="text/javascript">getDriversToManifest();</script>
        </hr>
        <div class='col-md-12' id='driver_list'>
        </div>
        <div class='col-md-12 text-center'>
            <button type='button' class='btn btn-primary' onclick='generateManifests()'>Submit</button>
        </div>
    </div>
</form>
@endsection
