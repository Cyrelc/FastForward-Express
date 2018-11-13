<script type='text/javascript' src='/js/bills/payment.js?{{config('view.version')}}'></script>
<div class='clearfix'>
<!-- delivery type -->
    <div class='panel panel-info'>
        <div class='panel-heading clearfix text-center'>
            <div class='col-lg-4 bottom15'>
                <div class='input-group'>
                    <span class='input-group-addon'>Payment Type: </span>
                    <select id='charge_type' class='form-control selectpicker' name='charge_type'>
                        <option value=''></option>
                        <option value='account' {{$model->bill->charge_account_id != null ? 'selected' : ''}}>Account</option>
                        <option value='driver' {{$model->bill->chargeback_id != null ? 'selected' : ''}}>Driver</option>
                        <option value='prepaid' {{$model->bill->payment_id != null ? 'selected' : ''}}>Prepaid</option>
                    </select>
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
                    <span class="input-group-addon">Driver Charge: $</span>
                    <input id="amount" name="amount" type="number" class="form-control" min="0.00" value="{{$model->bill->amount}}" step="0.01" />
                </div>
            </div>
        </div>
        <div class='panel-body clearfix'>
            <div class='tab-content'>
                <div id='charge_to_account' class='tab-pane fade'>
<!-- charge account -->
                    <div class="col-lg-8 bottom15">
                        <div class="input-group">
                            <span class="input-group-addon">Charge Account: </span>
                            <select id="charge_account_id" class="form-control selectpicker" name="charge_account_id" data-live-search='true' data-reference="charge_reference">
                                <option></option>
                                @foreach($model->accounts as $a)
                                    @if(isset($model->bill->charge_account_id) && $a->account_id == $model->bill->charge_account_id)
                                        <option selected value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
                                    @else
                                        <option value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
<!-- custom tracker -->
                    <div id='charge_reference' class="col-lg-4 {{$model->bill->charge_reference_value == '' ? 'hidden' : ''}} bottom15" id="charge_reference">
                        <div class="input-group">
                            <span id="charge_reference_name" class="input-group-addon" >{{$model->charge_reference_name}}</span>
                            <input id="charge_reference_value" name="charge_reference_value" class="form-control" type="text" disabled value="{{$model->bill->charge_reference_value}}" />
                        </div>
                    </div>
                </div>
<!-- charge to driver -->
                <div id='charge_to_driver' class='tab-pane fade'>
                    <div class='input-group'>
                        <span class='input-group-addon'>Charge Driver: </span>
                        <select id='charge_driver_id' name='charge_driver_id' class='form-control selectpicker' data-live-search='true'>
                            <option></option>
                            @foreach($model->employees as $e)
                                <option value='{{$e->employee_id}}' {{$model->chargeback->employee_id == $e->employee_id ? 'selected' : ''}}>{{$e->employee_number . ' - ' . $e->contact->first_name . ' ' . $e->contact->last_name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
<!-- prepaid -->
                <div id='charge_to_prepaid' class='tab-pane fade'>
                    <div class='col-md-6'>
                        <select id='prepaid_type' name='prepaid_type' class='form-control selectpicker'>
                            @foreach($model->prepaid_options as $prepaid_option)
                                <option value='{{$prepaid_option->value}}' {{$model->payment->payment_type == $prepaid_option->value ? 'selected' : ''}}>{{$prepaid_option->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class='col-md-6'>
                        <input type='text' id='prepaid_reference_value' name='prepaid_reference_value' class='form-control' value='{{$model->payment->reference_value}}' />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
