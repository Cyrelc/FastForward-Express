@extends ('layouts.app')

@section ('script')

<script type='text/javascript' src='{{URL::to('/')}}/js/bill.js'></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>

@parent
@endsection

@section ('content')
    @if (isset($model->bill->bill_id))
        <h2>Edit Bill</h2>
    @else
        <h2>New Bill</h2>
    @endif

<form method="POST" action="/bills/store">

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

<!--predetermined information -->
		<div class="col-lg-4 bottom15">
			<h4>Bill number: {{$model->bill->id}} </h4>
		</div>
		<div class="col-lg-4 bottom15">
			<h4>Invoice number: {{$model->bill->invoice_id}} </h4>
		</div>
		<div class="col-lg-4 bottom15">
			<h4>Manifest number: {{$model->bill->manifest_id}}</h4>
		</div>
<!--form-->
		<div id="account" class="bottom15 col-lg-4">
            <select id="account-id" class='form-control' name="account-id" data-id="-1">
                <option></option>
                @foreach ($model->accounts as $account)
                    @if (isset($model->bill->account_id) && $model->bill->account_id == $account->account_id)
                        <option selected value='{{$account->account_id}}'>{{$account->name}}</option>
                    @else
                        <option value='{{$account->account_id}}'>{{$account->name}}</option>
                    @endif
                @endforeach
            </select>
		</div>
        <div class="col-lg-4 bottom15">
            <select id="pickup_driver_id" class="form-control" name='pickup_driver_id'>
                <option></option>
                @foreach($model->drivers as $d)
                    @if (count($model->pickup_driver_id)) > 1 && $d->driver_id == $model->pickup_driver_id)
                        <option selected="selected" value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                    @else
                        <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                    @endif
                @endforeach
            </select>
        </div>
	</div>
</form>
@endsection

@section ('advFilter')
<div class="well form-group">
    <div class="checkbox">
    </div>
</div>
@endsection
