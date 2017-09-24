@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/bills/bill.js'></script>
<!-- <script type='text/javascript' src='{{URL::to('/')}}/js/storageService.js'></script>
 -->
@parent
@endsection

@section('style')
<link rel="stylesheet" type="text/css" href="{{URL::to('/')}}/css/bootstrap-combobox.css" />

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
    <div hidden class="col-lg-12">
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <h4 class="input-group-addon"> Bill Number: </h4>
                <input type="text" class="form-control" name="bill_id" readonly value="{{$model->bill->bill_id}}" style="background:0; border:0; outline:0;" />
            </div>
        </div>
        <div class="col-lg-4 bottom15">
            <div class="input-group"> 
                <h4 class="input-group-addon"> Invoice Number: </h4>
                <input type="text" class="form-control" name="invoice_id" readonly value="{{$model->bill->invoice_id}}" /> 
            </div>
        </div>
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <h4 class="input-group-addon"> Manifest Number: </h4>
                <input type="text" class="form-control" name="manifest_id" readonly value="{{$model->bill->manifest_id}}" />
            </div>
        </div>
    </div>
<!--form-->
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type='hidden' id="charge_selection_submission" name="charge_selection_submission" value='{{$model->charge_selection_submission}}'/>
    <input type='hidden' id='pickup_use_submission' name='pickup_use_submission' value='{{$model->pickup_use_submission}}' />
    <input type='hidden' id='delivery_use_submission' name='delivery_use_submission' value='{{$model->delivery_use_submission}}' />
    <input type='hidden' id='use_interliner' name='use_interliner' data-checkbox-id="use-interliner" value='{{$model->use_interliner}}' />
    <input type='hidden' id='skip_invoicing' name='skip_invoicing' data-checkbox-id='skip-invoicing' value='{{$model->skip_invoicing}}' />
<!-- delivery date -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Delivery Date: </span>
                <input type='text' id="date" class="form-control" name='date' placeholder="Delivery Date" value="{{date("l, F d Y", $model->bill->date)}}"/>
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
<!-- Amount -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Charge: $</span>
                <input id="amount" name="amount" type="number" class="form-control" min="0.00" value="{{$model->bill->amount}}" step="0.01" />
            </div>
        </div>
<!-- delivery type -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Delivery Type: </span>
                <select id="delivery_type" class="form-control" name="delivery_type">
                    <option></option>
                    @foreach($model->delivery_types as $delivery_type)
                        @if (isset($model->bill->delivery_type) && $delivery_type->value == $model->bill->delivery_type)
                            <option selected value="{{$delivery_type->value}}">{{$delivery_type->name}}</option>
                        @else
                            <option value="{{$delivery_type->value}}">{{$delivery_type->name}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
<!-- number of pieces -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Number of Pieces: </span>
                @if(isset($model->bill->num_pieces))
                    <input type="number" id="num_pieces" class="form-control" name="num_pieces" min="1" value="{{$model->bill->num_pieces}}" />
                @else
                    <input type="number" id="num_pieces" class="form-control" name="num_pieces" min="1" value="1" />
                @endif
            </div>
        </div>
<!-- Charge -->
        <div id="select_charge" class="col-lg-12 bottom15">
            <label><input id="charge_pickup_account" type="radio" name="charge_selection" {{$model->charge_selection_submission == 'pickup_account' ? 'checked' : ''}} />  Charge Pickup Account</label>
            <label><input id="charge_delivery_account" type="radio" name="charge_selection" {{$model->charge_selection_submission == 'delivery_account' ? 'checked' : ''}} />  Charge Delivery Account</label>
            <label><input id="charge_other_account" type="radio" name="charge_selection" {{$model->charge_selection_submission == 'other_account' ? 'checked' : ''}}/>  Charge Other Account</label>
            <label><input disabled id="pre_paid" type="radio" name="charge_selection" {{$model->charge_selection_submission == 'pre-paid' ? 'checked' : ''}}/>  Pre-Paid (Auto-Invoice)</label>
        </div>
        <div class="col-lg-4 hidden bottom15">
            <div class="input-group">
                <span class="input-group-addon">Payment Type:</span>
                <select id="payment_type" class="form-control" name="payment_type">
                    <option></option>
                    @foreach($model->payment_types as $payment_type)
                        @if (isset($model->bill->payment_type) && $payment_type == $model->payment_type)
                            <option selected value="{{$payment_type}}">{{$payment_type}}</option>
                        @else
                            <option value="{{$payment_type}}">{{$payment_type}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
<!-- charge account -->
        <div id="charge_account" class="col-lg-12 {{$model->charge_selection_submission == 'other_account' ? '' : 'hidden'}} bottom15">
            <div class="col-lg-6 bottom15">
                <div class="input-group">
                    <span class="input-group-addon">Charge Account: </span>
                    <select id="charge_account_id" class="form-control" name="charge_account_id" data-reference="charge_reference">
                        <option></option>
                        @foreach($model->accounts as $a)
                            @if (isset($model->bill->charge_account_id) && $a->account_id == $model->bill->charge_account_id)
                                <option selected value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->name}}</option>
                            @else
                                <option value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->name}}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
<!-- custom tracker -->
            <div id='charge_reference' class="col-lg-6 {{$model->bill->charge_reference_value == '' ? 'hidden' : ''}} bottom15" id="charge_reference">
                <div class="input-group">
                    <span id="charge_reference_name" class="input-group-addon" >{{$model->charge_reference_name}}</span>
                    <input id="charge_reference_value" name="charge_reference_value" class="form-control" type="text" value="{{$model->bill->charge_reference_value}}" />
                </div>
            </div>
        </div>
<!-- Interliner -->
        <div id="interliner">
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
<!-- Pickup -->
        <div class="col-lg-6 panel panel-default">
            <div class="panel-heading clearfix">
                <div class="col-lg-6">
                    <h4>Pickup</h4>
                </div>
                <div id="pickup_use_div" class="col-lg-6 btn-group bottom15" data-toggle="buttons">
                    <label class="radio-inline"><input id="pickup_use_account" type="radio" name="pickup_use" {{$model->pickup_use_submission == "account" ? 'checked' : ''}} {{$model->charge_selection_submission == "pickup_account" ? 'disabled' : ''}} />  Use Account</label>
                    <label class="radio-inline"><input id="pickup_use_address" type="radio" name="pickup_use" {{$model->pickup_use_submission == "address" ? 'checked' : ''}} {{$model->charge_selection_submission == "pickup_account" ? 'disabled' : ''}}/>  Use Address</label>
                </div>
            </div>
<!--pickup driver-->
            <div class="panel-body">
                <div class="col-lg-8 bottom15">
                    <div class="input-group">
                        <span class="input-group-addon">Pickup Driver: </span>
                        <select id="pickup_driver_id" class="form-control" name='pickup_driver_id'>
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
<!-- pickup account -->
                <div id="pickup_account" class="col-lg-12 {{$model->pickup_use_submission == 'address' ? 'hidden' : ''}} bottom15 clearfix">
                    <div class="col-lg-12 bottom15">
                        <div class="input-group">
                            <span class="input-group-addon">Pickup Account: </span>
                            <select id="pickup_account_id" class="form-control" name="pickup_account_id" data-reference="pickup_reference">
                                <option></option>
                                @foreach($model->accounts as $a)
                                    @if (isset($model->bill->pickup_account_id) && $a->account_id == $model->bill->pickup_account_id)
                                        <option selected value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->name}}</option>
                                    @else
                                        <option value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
<!-- custom tracker -->
                    <div id='pickup_reference' class="col-lg-12 {{$model->bill->pickup_reference_value == '' ? 'hidden' : ''}} bottom15" name="pickup_reference">
                        <div class="input-group">
                            <span id="pickup_reference_name" class="input-group-addon" >{{$model->pickup_reference_name}}</span>
                            <input id="pickup_reference_value" name="pickup_reference_value" class="form-control" type="text" value="{{$model->bill->pickup_reference_value}}" />
                        </div>
                    </div>
                </div>
<!--pickup address -->
                <div id="pickup_address" class="col-lg-12 {{$model->pickup_use_submission == "account" ? 'hidden' : ''}}" >
                    @include('partials.address', ['prefix' => 'pickup', 'address' => $model->pickupAddress, 'enabled' => true])
                </div>
            </div>
        </div>
<!-- Delivery -->
        <div class="col-lg-6 panel panel-default">
            <div class="panel-heading clearfix">
                <div class="col-lg-6">
                    <h4>Delivery</h4>
                </div>
                <div class="col-lg-6 btn-group bottom15" data-toggle="buttons">
                    <label class="radio-inline"><input id="delivery_use_account" type="radio" name="delivery_use" {{$model->delivery_use_submission == "account" ? 'checked' : ''}} {{$model->charge_selection_submission == "delivery_account" ? 'disabled' : ''}} />  Use Account</label>
                    <label class="radio-inline"><input id="delivery_use_address" type="radio" name="delivery_use" {{$model->delivery_use_submission == "address" ? 'checked' : ''}} {{$model->charge_selection_submission == "delivery_account" ? 'disabled' : ''}} />  Use Address</label>
                </div>
            </div>
<!-- delivery driver -->
            <div class="panel-body">
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
<!-- delivery account -->
                <div id="delivery_account" class="col-lg-12 {{$model->delivery_use_submission == 'address' ? 'hidden' : ''}} bottom15">
                    <div class="col-lg-12 bottom15">
                        <div class="input-group">
                            <span class="input-group-addon">Delivery Account: </span>
                            <select id="delivery_account_id" class="form-control" name="delivery_account_id" data-reference="delivery_reference">
                                <option></option>
                                @foreach($model->accounts as $a)
                                    @if (isset($model->bill->delivery_account_id) && $a->account_id == $model->bill->delivery_account_id)
                                        <option selected value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->name}}</option>
                                    @else
                                        <option value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
<!-- custom tracker -->
                    <div id='delivery_reference' class="col-lg-12 {{$model->bill->delivery_reference_value == '' ? 'hidden' : ''}} bottom15" name="delivery_reference">
                        <div class="input-group">
                            <span id="delivery_reference_name" class="input-group-addon" >{{$model->delivery_reference_name}}</span>
                            <input id="delivery_reference_value" name="delivery_reference_value" class="form-control" type="text" value="{{$model->bill->delivery_reference_value}}" />
                        </div>
                    </div>
                </div>
<!-- delivery address -->
                <div id="delivery_address" class="col-lg-12 {{$model->delivery_use_submission == "account" ? 'hidden' : ''}}">
                    @include('partials.address', ['prefix' => 'delivery', 'address' => $model->deliveryAddress, 'enabled' => true])
                </div>
            </div>
        </div>
<!-- Description -->
        <div class="col-lg-12 bottom15">
            <label for="description">Description: </label>
            <textarea class="form-control" rows="5" name="description" placeholder="Any details pertaining to this bill">{{$model->bill->description}}</textarea>
        </div>
	</div>
    <div class='text-center'>
        <button type='submit' class='btn btn-primary'>Submit</button>
    </div>
</form>
@endsection

@section ('advFilter')
<div class="well form-group" id="keep-options">
    <h4>On Submit</h4>
    <hr>
    <div class="checkbox">
        <label><input disabled id="keep_date" type="checkbox" name="keep_date" />Keep Date</label>
    </div>
    <div class="checkbox">
        <label><input disabled id="keep_charge_selection" type="checkbox" name="keep_charge_selection" />Keep Charge Selection</label>
    </div>
    <div class="checkbox">
        <label><input disabled id="keep_charge_account" type="checkbox" name="keep_charge_account" />Keep Charge Account</label>
    </div>
    <div class="checkbox">
        <label><input disabled id="keep_pickup_account" type="checkbox" name="keep_pickup_account" />Keep Pickup Account</label>
    </div>
    <div class="checkbox">
        <label><input disabled id="keep_delivery_account" type="checkbox" name="keep_delivery_account" />Keep Delivery Account</label>
    </div>
    <div class="checkbox">
        <label><input disabled id="keep_pickup_driver" type="checkbox" name="keep_pickup_driver" />Keep Pickup Driver</label>
    </div>
    <div class="checkbox">
        <label><input disabled id="keep_delivery_driver" type="checkbox" name="keep_delivery_driver" />Keep Delivery Driver</label>
    </div>
    <hr>
    <div class="checkbox">
        <label><input id="use-interliner" type="checkbox" name="use-interliner" data-hidden-name="use_interliner" data-div="interliner" />Use Interliner</label>
    </div>
    <div class="checkbox">
        <label><input id="skip-invoicing" type="checkbox" name="skip-invoicing" data-hidden-name="skip_invoicing" />Skip Invoicing</label>
    </div>
</div>
@endsection
