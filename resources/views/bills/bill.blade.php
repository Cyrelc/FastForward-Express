@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/bills/bill.js'></script>

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
		<div hidden class="col-lg-4 bottom15">
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
<!-- delivery date -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Delivery Date: </span>
                <input type='text' id="delivery_date" class="form-control" name='delivery_date' placeholder="Delivery Date" value="{{date("l, F d Y", $model->bill->delivery_date)}}"/>
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>
<!-- bill number -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Waybill Number: </span>
                <input id="bill_number" name="bill_number" type="text" class="form-control" value="{{$model->bill->bill_number}}" />
            </div>
        </div>
        <div class="col-lg-12 panel panel-default">
            <div class="panel-heading">
            </div>
            <div class="panel-body">
<!-- Account selection-->
        		<div id="account" class="col-lg-4 bottom15">
                    <div class="input-group">
                        <span class="input-group-addon">Account: </span>
                        <select id="account_id" class='form-control' name="account_id" data-id="-1">
                            <option></option>
                            @foreach ($model->accounts as $account)
                                @if (isset($model->bill->account_id) && $model->bill->account_id == $account->account_id)
                                    <option selected value='{{$account->account_id}}' data-reference-field-name="{{$account->custom_field}}">{{$account->name}}</option>
                                @else
                                    <option value='{{$account->account_id}}'>{{$account->name}}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
        		</div>
<!-- custom tracker -->
                <div hidden class="col-lg-4 bottom15" id="reference_field">
                    <div class="input-group">
                        <span id="reference_field_name" class="input-group-addon"></span>
                        <input disabled id="reference_id" name="reference_id" class="form-control" type="text" value="Coming soon!" value="{{$model->bill->reference_id}}" />
                    </div>
                </div>
<!-- Amount -->
                <div class="col-lg-4 bottom15">
                    <div class="input-group">
                        <span class="input-group-addon">$ </span>
                        <input id="amount" name="amount" type="number" class="form-control" min="0.00" value="{{$model->bill->amount}}" step="0.01" />
                    </div>
                </div>
<!-- Pickup Driver -->
                <div class="col-lg-8 bottom15">
                    <div class="input-group">
                        <span class="input-group-addon">Pickup Driver: </span>
                        <select id="pickup_driver_id" class="form-control" name='pickup_driver_id' onselect="">
                            <option></option>
                            @foreach($model->drivers as $d)
                                @if (isset($model->bill->pickup_driver_id) && $d->driver_id == $model->bill->pickup_driver_id)
                                    <option selected value="{{$d->driver_id}}" data-driver-commission="{{$d->pickup_commission}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                @else
                                    <option value="{{$d->driver_id}}" data-driver-commission="{{$d->pickup_commission}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-4 bottom15">
                    <div class="input-group">
                        <input id="pickup_driver_commission" class="form-control" type="number" min="0" max="100" name="pickup_driver_commission" value="{{$model->bill->pickup_driver_commission}}"/>
                        <span class="input-group-addon">%</span>
                    </div>
                </div>
<!-- Delivery Driver -->
                <div class="col-lg-8 bottom15">
                    <div class="input-group">
                        <span class="input-group-addon">Delivery Driver: </span>
                        <select id="delivery_driver_id" class="form-control" name="delivery_driver_id">
                            <option></option>
                            @foreach($model->drivers as $d)
                                @if (isset($model->bill->delivery_driver_id) && $d->driver_id == $model->bill->delivery_driver_id)
                                    <option selected value="{{$d->driver_id}}" data-driver-commission="{{$d->delivery_commission}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                @else
                                    <option value="{{$d->driver_id}}" data-driver-commission="{{$d->delivery_commission}}">{{$d->contact->first_name . ' ' . $d->contact->last_name}}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-4 bottom15">
                    <div class="input-group">
                        <input id="delivery_driver_commission" class="form-control" type="number" min="0" max="100" name="delivery_driver_commission" value="{{$model->bill->delivery_driver_commission}}" />
                        <span class="input-group-addon">%</span>
                    </div>
                </div>
<!-- Interliner -->
                <div class="col-lg-8 bottom15">
                    <div class="input-group">
                        <span class="input-group-addon">Interliner: </span>
                        <select id="interliner_id" class="form-control" name="interliner_id">
                            <option></option>
                            @foreach($model->interliners as $i)
                                @if (isset($model->bill->interliner_id) && $i->interliner_id == $model->bill->interliner_id)
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
        <div class="col-lg-12 bottom15">
            <label for="description">Description: </label>
            <textarea class="form-control" rows="5" name="description" placeholder="Any details pertaining to this bill">{{$model->bill->description}}</textarea>
        </div>
        <div class='text-center'>
            <button type='submit' class='btn btn-primary'>Submit</button>
        </div>
	</div>
</form>
@endsection

@section ('advFilter')
<div class="well form-group">
    <h3>On Submit</h3>
    <hr>
    <div class="checkbox">
        <label><input id="keep_account" type="checkbox" name="keep_account" />Keep Account</label>
    </div>
    <div class="checkbox">
        <label><input id="keep_pickup_driver" type="checkbox" name="keep_pickup_driver" />Keep Pickup Driver</label>
    </div>
    <div class="checkbox">
        <label><input id="keep_delivery_driver" type="checkbox" name="keep_delivery_driver" />Keep Delivery Driver</label>
    </div>
</div>
@endsection
