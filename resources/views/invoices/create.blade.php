@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/bills/bill.js'></script>

@parent
@endsection

@section('style')
<link rel="stylesheet" type="text/css" href="{{URL::to('/')}}/css/bootstrap-combobox.css" />

@parent
@endsection

@section('content')

<h2>Generate Invoices</h2>

<form method="POST" action="/invoices/generate">

	<div class="clearfix well">
        <pre id='errors' class='hidden'></pre>
        @if(!empty($errors) && $errors->count() > 0)
            <br />
            <div class="col-lg-12">
                <div class="alert alert-danger">
                    <p>The following errors occurred on submit:</p>
                    <ul>
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

<!--Invoice Interval-->
        <div class="col-lg-4 bottom15">
            <select class='form-control' name="invoice-interval" placeholder="Select Invoice Interval">
                @foreach ($model->invoice_intervals as $ii)
                    <option value="{{$ii}}">{{ucfirst($ii)}}</option>
                @endforeach
            </select>
        </div>

<!-- start date -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Start Date: </span>
                <input type='text' id="start_date" class="form-control" name='start_date' placeholder="Start Date" value="{{date("l, F d Y", $model->start_date)}}"/>
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>

<!-- end date -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">End Date: </span>
                <input type='text' id="end_date" class="form-control" name='end_date' placeholder="End Date" value="{{date("l, F d Y", $model->end_date)}}"/>
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>

<!-- sort by -->
		<div>
			
		</div>
    </div>
@endsection
