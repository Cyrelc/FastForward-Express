@extends ('layouts.app')

@section ('script')

<!-- <script type='text/javascript' src='{{URL::to('/')}}/js/bill.js'></script> -->
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
		<div hidden class="col-lg-4 bottom15">
			<h4>Invoice number: {{$model->bill->invoice_id}} </h4>
		</div>
		<div hidden class="col-lg-4 bottom15">
			<h4>Manifest number: {{$model->bill->manifest_id}}</h4>
		</div>
        <hr>
<!--form-->
        <div class="col-lg-12">
            <div class="col-lg-6 panel panel-default">
                <div class="panel-heading">
                </div>
                <div class="panel-body">
            		<div id="account" class="col-lg-12 bottom15">
                        <div class="input-group">
                            <span class="input-group-addon">Account: </span>
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
            		</div>
                    <div class="col-lg-8 bottom15">
                        <div class="input-group">
                            <span class="input-group-addon">Pickup Driver: </span>
                            <select id="pickup_driver_id" class="form-control" name='pickup_driver_id'>
                                <option></option>
                                @foreach($model->drivers as $d)
                                    @if (count($model->bill->pickup_driver_id) > 1 && $d->driver_id == $model->bill->pickup_driver_id)
                                        <option selected value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                    @else
                                        <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <!--TODO auto populate these fields when a driver is selected -->
                    <div class="col-lg-4 bottom15">
                        <div class="input-group">
                            <input id="pickup_driver_percentage" class="form-control" type="number" min="0" max="100" name="pickup_driver_percentage" />
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    <div class="col-lg-8 bottom15">
                        <div class="input-group">
                            <span class="input-group-addon">Delivery Driver: </span>
                            <select id="delivery_driver_id" class="form-control" name="delivery_driver_id">
                                <option></option>
                                @foreach($model->drivers as $d)
                                    @if (count($model->bill->delivery_driver_id) > 1 && $d->driver_id == $model->bill->delivery_driver_id)
                                        <option selected value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                    @else
                                        <option value="{{$d->driver_id}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4 bottom15">
                        <div class="input-group">
                            <input id="delivery_driver_percentage" class="form-control" type="number" min="0" max="100" name="delivery_driver_percentage" />
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    <div class="col-lg-8 bottom15">
                        <div class="input-group">
                            <span class="input-group-addon">Interliner: </span>
                            <select id="interliner_id" class="form-control" name="interliner_id">
                                <option></option>
                                @foreach($model->interliners as $i)
                                    @if (count($model->bill->interliner_id) > 1 && $i->interliner_id == $model->bill->interliner_id)
                                        <option selected value="{{$i->interliner_id}}">{{$i->name}}</option>
                                    @else
                                        <option value="{{$i->interliner_id}}">{{$i->name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4 bottom15">
                        <div class="input-group">
                            <span class="input-group-addon">$</span>
                            <input id="interliner_amount" name="interliner_amount" type="number" class="form-control" min="0" value="{{$model->bill->interliner_amount}}" step="0.01" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='text-center'><button type='submit' class='btn btn-primary'>Submit</button></div>
	</div>
</form>
@endsection

@section ('advFilter')
<div class="well form-group">
    <div class="checkbox">
    </div>
</div>
@endsection
