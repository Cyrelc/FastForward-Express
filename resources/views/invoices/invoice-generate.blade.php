@extends ('layouts.app')

@section ('script')
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/invoices/invoice-generate.js'></script>

@parent
@endsection

@section('style')
<link rel="stylesheet" type="text/css" href="{{URL::to('/')}}/css/bootstrap-combobox.css" />

@parent
@endsection

@section('content')

<h2>Generate Invoices</h2>

<form method="POST" action="/invoices/store">

    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="account_count" id="account_count">

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
            <div class="input-group">
                <span class="input-group-addon">Invoice Interval: </span>
                <select class='form-control' id='invoice-interval' name="invoice-interval" placeholder="Select Invoice Interval">
                    @foreach ($model->invoice_intervals as $ii)
                        <option value="{{$ii}}">{{ucfirst($ii)}}</option>
                    @endforeach
                </select>
            </div>
        </div>

<!-- start date -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Start Date: </span>
                <input type='text' id="start_date" class="form-control" name='start_date' value="{{date("l, F d Y", $model->start_date)}}"/>
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>

<!-- end date -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">End Date: </span>
                <input type='text' id="end_date" class="form-control" name='end_date' value="{{date("l, F d Y", $model->end_date)}}"/>
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>
        <div class='text-center'>
            <button type='button' class='btn btn-info' onclick="getAccountsToInvoice()" >Update Account List</button>
        </div>
<!-- preview list -->
        <div class="col-lg-12">
            <hr>
            <h5>The following accounts fit the chosen criteria, and have bills that are yet to be invoiced:</h5>
        </div>
        <div class="col-lg-12 bottom15">
            <h5 id='preview_list_placeholder' style="color:red">Currently no accounts are selected to invoice</h5>

            <table id="account_preview_table" name="account_preview_table" style="width:100%">
                <thead>
                    <tr>
                        <th>Invoice?</th>
                        <th>Account Name</th>
                        <th>Number of Bills Matched</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <div class='text-center'>
            <button type='submit' class='btn btn-primary'>Submit</button>
        </div>
    </div>
</form>
@endsection
