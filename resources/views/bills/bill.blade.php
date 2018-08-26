@extends ('layouts.app')

@section ('script')

<script type="text/javascript" src="/js/bootstrap-combobox.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript' src='/js/bills/bill.js?8-02-2018'></script>
<script type='text/javascript' src='/js/toastr.min.js'></script>
@parent
@endsection

@section('style')
<link rel="stylesheet" type="text/css" href="/css/bootstrap-combobox.css" />
<link rel='stylesheet' type='text/css' href='/css/toastr.min.css' />
@parent
@endsection

@section ('content')

@if(isset($model->bill->bill_id))
    <h2>Edit Bill</h2>
    @php($is_new = false)
@else
    <h2>New Bill</h2>
    @php($is_new = true)
@endif

<form id='bill-form'>
	<div class="clearfix well">
<!--predetermined information -->
    <div hidden class="col-lg-12">
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <h4 class="input-group-addon"> Bill Number: </h4>
                <input type="text" class="form-control" id='bill_id' name="bill_id" readonly value="{{$model->bill->bill_id}}" style="background:0; border:0; outline:0;" />
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
    <input type='hidden' id='pickup_use_submission' name='pickup_use_submission' value='{{$model->pickup_use_submission}}' />
    <input type='hidden' id='delivery_use_submission' name='delivery_use_submission' value='{{$model->delivery_use_submission}}' />
    <input type='hidden' id='use_interliner' name='use_interliner' data-checkbox-id="use-interliner" value='{{$model->use_interliner}}' />
    <input type='hidden' id='skip_invoicing' name='skip_invoicing' data-checkbox-id='skip-invoicing' value='{{$model->skip_invoicing}}' />
<!-- delivery date -->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Delivery Date: </span>
                <input type='text' id="date" class="form-control" name='date' placeholder="Delivery Date" value="@if($is_new && Cookie::get('bill_keep_date')) {{Cookie::get('bill_keep_date')}} @else {{date("l, F d Y", $model->bill->date)}} @endif"/>
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
                <select id="delivery_type" class="form-control selectpicker" name="delivery_type">
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
<!-- Charge -->
        <div class="col-lg-12 bottom15">
            <div class='input-group'>
                <span class='input-group-addon'>Charge to: </span>
                <select id="charge_selection" class='form-control selectpicker btn btn-primary' name='charge_selection'>
                    <option @if(($is_new && Cookie::get('bill_keep_charge_selection') == null) || ($is_new && Cookie::get('bill_keep_charge_selection') == 'pickup_account')) selected @elseif(!$is_new && $model->charge_selection_submission == 'pickup_account') selected @elseif($is_new && !Cookie::get('bill_keep_charge_selection')) selected @endif value='pickup_account'>Pickup Account</option>
                    <option @if($is_new && Cookie::get('bill_keep_charge_selection') == 'delivery_account') selected @elseif(!$is_new && $model->charge_selection_submission == 'delivery_account') selected @endif value='delivery_account'>Delivery Account</option>
                    <option @if($is_new && Cookie::get('bill_keep_charge_selection') == 'other_account') selected @elseif(!$is_new && $model->charge_selection_submission == 'other_account') selected @endif value='other_account'>Other Account</option>
                    <option disabled @if($is_new && Cookie::get('bill_keep_charge_selection') == 'pre_paid') selected @elseif(!$is_new && $model->charge_selection_submission == 'pre_paid') selected @endif value='pre_paid'>Pre Paid</option>
                    <option disabled @if($is_new && Cookie::get('bill_keep_charge_selection') == 'driver') selected @elseif(!$is_new && $model->charge_selection_submission == 'driver') selected @endif value='driver'>Driver</option>
                </select>
            </div>
        </div>
<!-- Payment Type -->
        <div class="col-lg-4 hidden bottom15">
            <div class="input-group">
                <span class="input-group-addon">Payment Type:</span>
                <select id="payment_type" class="form-control selectpicker" name="payment_type">
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
                    <select id="charge_account_id" class="form-control selectpicker" name="charge_account_id" data-live-search='true' data-reference="charge_reference">
                        <option></option>
                        @foreach($model->accounts as $a)
                            @if(($is_new && Cookie::get('bill_keep_charge_account') == $a->account_id) || (isset($model->bill->charge_account_id) && $a->account_id == $model->bill->charge_account_id))
                                <option selected value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
                            @else
                                <option value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
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
                    <select id="interliner_id" class="form-control selectpicker" data-live-search='true' name="interliner_id">
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
                        <select id="pickup_driver_id" class="form-control selectpicker" data-live-search='true' name='pickup_driver_id'>
                            <option></option>
                            @foreach($model->employees as $e)
                                @if(($is_new && Cookie::get('bill_keep_pickup_driver') == $e->driver->driver_id) || (isset($model->bill->pickup_driver_id) && $e->driver->driver_id == $model->bill->pickup_driver_id))
                                    <option selected value="{{$e->driver->driver_id}}" data-driver-commission="{{$e->driver->pickup_commission}}">{{$e->employee_number . ' - ' . $e->contact->first_name . ' ' . $e->contact->last_name}}</option>
                                @else
                                    <option value="{{$e->driver->driver_id}}" data-driver-commission="{{$e->driver->pickup_commission}}">{{$e->employee_number . ' - ' . $e->contact->first_name . ' ' . $e->contact->last_name}}</option>
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
                            <select id="pickup_account_id" class="form-control selectpicker" data-live-search='true' name="pickup_account_id" data-reference="pickup_reference">
                                <option></option>
                                @foreach($model->accounts as $a)
                                    @if(($is_new && Cookie::get('bill_keep_pickup_account') == $a->account_id) || (isset($model->bill->pickup_account_id) && $a->account_id == $model->bill->pickup_account_id))
                                        <option selected value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
                                    @else
                                        <option value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
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
                        <select id="delivery_driver_id" class="form-control selectpicker" data-live-search='true' name="delivery_driver_id">
                            <option></option>
                            @foreach($model->employees as $e)
                                @if(($is_new && Cookie::get('bill_keep_delivery_driver') == $e->driver->driver_id) || (isset($model->bill->delivery_driver_id) && $e->driver->driver_id == $model->bill->delivery_driver_id))
                                    <option selected value="{{$e->driver->driver_id}}" data-driver-commission="{{$e->driver->delivery_commission}}">{{$e->employee_number . ' - ' . $e->contact->first_name . ' ' . $e->contact->last_name}}</option>
                                @else
                                    <option value="{{$e->driver->driver_id}}" data-driver-commission="{{$e->driver->delivery_commission}}">{{$e->employee_number . ' - ' . $e->contact->first_name . ' ' . $e->contact->last_name}}</option>
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
                            <select id="delivery_account_id" class="form-control selectpicker" data-live-search='true' name="delivery_account_id" data-reference="delivery_reference">
                                <option></option>
                                @foreach($model->accounts as $a)
                                    @if(($is_new && Cookie::get('bill_keep_delivery_account') == $a->account_id) || (isset($model->bill->delivery_account_id) && $a->account_id == $model->bill->delivery_account_id))
                                        <option selected value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
                                    @else
                                        <option value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
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
<!-- Piece Information -->
         <div class="col-lg-12 bottom15">
            <input type="hidden" id="next_piece_id" value="0"/>
            <input type="hidden" name="delete_packages" />
            <table id='package_table' class="table table-bordered">
                <thead class="thead-inverse">
                    <th><button type="button" id="add_package">Add Package</button></th>
                    <th>Weight</th>
                    <th>Length</th>
                    <th>Width</th>
                    <th>Height</th>
                <thead>
                <tbody>
                    @foreach($model->packages as $package)
                        <script type="text/javascript">addPackage({{$package->weight}}, {{$package->length}}, {{$package->width}}, {{$package->height}}, {{$package->package_id}});</script>
                    @endforeach
                </tbody>
            </table>
        </div>
 <!-- Description -->
        <div class="col-lg-12 bottom15">
            <label for="description">Description: </label>
            <textarea class="form-control" rows="5" name="description" placeholder="Any details pertaining to this bill">{{$model->bill->description}}</textarea>
        </div>
	</div>
    <div class='text-center'>
        <button type='button' class='btn btn-primary' onclick='storeBill()'>Submit</button>
    </div>
</form>
@endsection

@section ('advFilter')
<div class="well form-group">
    <form id='bill-persistence-form'>
        @if($is_new)
            <h4>On Submit</h4>
            <hr>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_date" {{Cookie::get('bill_keep_date') ? 'checked' : ''}} />Keep Date</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_charge_selection" {{Cookie::get('bill_keep_charge_selection') ? 'checked' : ''}} />Keep Charge Selection</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_charge_account" {{Cookie::get('bill_keep_charge_account') ? 'checked' : ''}} />Keep Charge Account</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_pickup_account" {{Cookie::get('bill_keep_pickup_account') ? 'checked' : '' }} />Keep Pickup Account</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_delivery_account" {{Cookie::get('bill_keep_delivery_account') ? 'checked' : '' }} />Keep Delivery Account</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_pickup_driver" {{Cookie::get('bill_keep_pickup_driver') ? 'checked' : '' }} />Keep Pickup Driver</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="keep_delivery_driver" {{Cookie::get('bill_keep_delivery_driver') ? 'checked' : '' }} />Keep Delivery Driver</label>
            </div>
        @endif
    </form>
    <hr>
    <div class="checkbox">
        <label><input id="use-interliner" type="checkbox" name="use-interliner" data-hidden-name="use_interliner" data-div="interliner" />Use Interliner</label>
    </div>
    <div class="checkbox">
        <label><input id="skip-invoicing" type="checkbox" name="skip-invoicing" data-hidden-name="skip_invoicing" />Skip Invoicing</label>
    </div>
</div>
@endsection
