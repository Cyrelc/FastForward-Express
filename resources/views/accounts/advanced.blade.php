<form id='account_advanced'>
    <div class='clearfix'>
<!--Account Number-->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class='input-group-addon'>Account Number</span>
                <input class='form-control' id="account_number" type='text' name='account_number' placeholder="Previous Account Number" value="{{$model->account->account_number}}"/>
                <span class="input-group-addon" id="account_number_result"></span>
            </div>
        </div>
<!-- Parent Account -->
        <div id="parent_location" class="bottom15 col-lg-4" >
            <div class='input-group'>
                <span class='input-group-addon'>Parent Account</span>
                <select id="parent_account_id" class='form-control selectpicker' name="parent_account_id" data-live-search='true' >
                    <option></option>
                    @foreach ($model->accounts as $parent)
                        @if (isset($model->account->account_id) && $model->account->parent_account_id == $parent->account_id)
                            <option selected value='{{$parent->account_id}}'>{{$parent->name}}</option>
                        @else
                            <option value='{{$parent->account_id}}'>{{$parent->name}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
<!--Start Date-->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class="input-group-addon">
                    Start Date
                </span>
                <input type='text' id="start_date" class="form-control" name='start_date' placeholder="Start Date" value="{{date("l, F d Y", $model->account->start_date)}}"/>
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>
<!--Billing Options-->
        </br>
        <h4>Billing Options</h4>
        <hr>
<!--Ratesheet -->
        <div class="col-lg-4 bottom15">
            <div class='input-group'>
                <span class='input-group-addon'>Ratesheet</span>
                <select class='form-control' id='ratesheet_id' name='ratesheet_id' >
                    <option value='' {{$model->account->ratesheet_id === null ? 'selected' : null}}>Select Rate</option>
                    @foreach($model->ratesheets as $ratesheet)
                        <option {{$ratesheet->ratesheet_id === $model->account->ratesheet_id ? 'selected' : null}} value={{$ratesheet->ratesheet_id}}>{{$ratesheet->ratesheet_id}} - {{$ratesheet->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
<!--Minimum Invoice Amount-->
        <div class='col-lg-4 bottom15' id='min_invoice_amount_div'>
            <span class='input-group'>
                <span class='input-group-addon'>Minimum Invoice Payment $</span>
                <input class='form-control' min=0 max=100 step='0.01' type='number' name='min_invoice_amount' placeholder='Minimum Payment' value='{{$model->account->min_invoice_amount}}' />
            </span>
        </div>
<!--Discount-->
        <div class="col-lg-4 bottom15" id="discount-div">
            <div class="input-group">
                <span class='input-group-addon'>Discount</span>
                <input class='form-control' min=0 max=100 step='0.01' type='number' name='discount' placeholder="Discount %" value="{{$model->account->has_discount == 1 ? $model->account->discount : ""}}" />
                <span class="input-group-addon">%</span>
            </div>
        </div>
<!-- Fuel Surcharge-->
        <div class="col-lg-4 bottom15" id="fuel-surcharge">
            <div class='input-group'>
                <span class='input-group-addon'>Fuel Surcharge</span>
                <input class='form-control' min=0 max=100 step='0.01' type='number' name="fuel_surcharge" placeholder="Fuel surcharge %" value="{{$model->account->fuel_surcharge}}" />
                <span class='input-group-addon'>%</span>
            </div>
        </div>
        <div class='col-lg-4 bottom15' id='charge_interest'>
            <h4>INTEREST LOGIC GOES HERE</h4>
        </div>
        <br>
        <h4>Configuration</h4>
        <hr>
<!--configuration-->
        <div class='col-md-3 form-check form-check-inline'>
            <input type="checkbox" id='is_gst_exempt' name='is_gst_exempt' class='form-check-input checkbox-lg' {{$model->account->gst_exempt == 1 ? 'checked' : ''}}>
            <label class='form-check-label' for='isGstExempt'>Is GST Exempt</label>
        </div>
        <div class='col-md-3 form-check form-check-inline'>
            <input type="checkbox" id='can_be_parent' name='can_be_parent' class='form-check-input checkbox-lg' {{$model->account->can_be_parent == 1 ? 'checked' : ''}}>
            <label class='form-check-label' for='canBeParent'>Can be Parent</label>
        </div>
        <div class="col-md-3 form-check form-check-inline">
            <input type="checkbox" id='send_bills' name="send_bills" class='form-check-input checkbox-lg' {{$model->account->send_bills == 1 ? 'checked' : ''}}>
            <label class='form-check-label' for='send_bills'>Send Bills</label>
        </div>
        <div class="col-md-3 form-check form-check-inline">
            <input type="checkbox" id='send_invoices' name="send_invoices" class='form-check-input checkbox-lg' {{$model->account->send_invoices == 1 ? 'checked' : ''}}>
            <label class='form-check-label' for='send_bills'>Send Invoices</label>
        </div>
        @if(isset($model->parentAccount->account_id))
            <div class="col-md-3 form-check form-check-inline">
                <input type="checkbox" id='use_parent_ratesheet' name="use_parent_ratesheet" class='form-check-input checkbox-lg' {{$model->account->send_invoices == 1 ? 'checked' : ''}}>
                <label class='form-check-label' for='use_parent_ratesheet'>Use Parent Ratesheet</label>
            </div>
        @endif
    </div>
</form>
