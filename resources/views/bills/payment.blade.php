<div class='clearfix'>
<!-- delivery type -->
    <div class='panel panel-info'>
        <div class='panel-heading clearfix text-center'>
            <div class='col-lg-4 bottom15'>
                <div class='input-group'>
                    <span class='input-group-addon'>Payment Type: </span>
                    <select id='payment_type' class='form-control selectpicker' name='payment_type'>
                        <option value='account'>Account</option>
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
<!-- charge account -->
            <div id="charge_account" class="col-lg-8 bottom15">
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
            <div id='charge_reference' class="col-lg-4 {{$model->bill->charge_reference_value == '' ? 'hidden' : ''}} bottom15" id="charge_reference">
                <div class="input-group">
                    <span id="charge_reference_name" class="input-group-addon" >{{$model->charge_reference_name}}</span>
                    <input id="charge_reference_value" name="charge_reference_value" class="form-control" type="text" value="{{$model->bill->charge_reference_value}}" />
                </div>
            </div>
        </div>
    </div>
</div>
