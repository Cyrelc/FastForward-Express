<form id='account_advanced'>
    <div class='clearfix'>
<!--Account Number-->
        <div class="col-lg-4 bottom15">
            <div class="input-group">
                <span class='input-group-addon'>Account Number</span>
                <input class='form-control' id="account_number" type='text' name='account-number' placeholder="Previous Account Number" value="{{$model->account->account_number}}"/>
                <span class="input-group-addon" id="account_number_result"></span>
            </div>
        </div>
<!-- Parent Account -->
        <div id="parent-location" class="bottom15 col-lg-4" >
            <div class='input-group'>
                <span class='input-group-addon'>Parent Account</span>
                <select id="parent-account-id" class='form-control' name="parent-account-id">
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
                <input type='text' id="start-date" class="form-control" name='start-date' placeholder="Start Date" value="{{date("l, F d Y", $model->account->start_date)}}"/>
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>
<!--Billing Options-->
        </br>
        <h4>Billing Options</h4>
        <hr>
<!--Rate Type -->
        <div class="col-lg-4 bottom15">
            <select class='form-control' name="rate-id" disabled >
                <option value="-1" selected disabled>Select Rate (coming soon!)</option>
            </select>
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
                <input class='form-control' min=0 max=100 step='0.01' type='number' name="fuel-surcharge" placeholder="Fuel surcharge %" value="{{$model->account->fuel_surcharge}}" />
                <span class='input-group-addon'>%</span>
            </div>
        </div>
        <div class='col-lg-4 bottom15' id='charge-interest'>
            <h4>INTEREST LOGIC GOES HERE</h4>
        </div>
        <br>
        <h4>Configuration</h4>
        <hr>
<!--configuration-->
        <div class='col-md-3 form-check form-check-inline'>
            <input type="checkbox" id='isGstExempt' name='isGstExempt' class='form-check-input checkbox-lg' {{$model->account->gst_exempt == 1 ? 'checked' : ''}}>
            <label class='form-check-label' for='isGstExempt'>Is GST Exempt</label>
        </div>
        <div class='col-md-3 form-check form-check-inline'>
            <input type="checkbox" id='canBeParent' name='canBeParent' class='form-check-input checkbox-lg' {{$model->account->can_be_parent == 1 ? 'checked' : ''}}>
            <label class='form-check-label' for='canBeParent'>Can be Parent</label>
        </div>
        <div class="col-md-3 form-check form-check-inline">
            <input type="checkbox" id='send_bills' name="send_bills" class='form-check-input checkbox-lg' {{$model->account->send_bills == 1 ? 'checked' : ''}}>
            <label class='form-check-label' for='send_bills'>Send Bills</label>
        </div>
        <div class="col-md-3 form-check form-check-inline">
            <input type="checkbox" id='send_bills' name="send_invoices" class='form-check-input checkbox-lg' {{$model->account->send_invoices == 1 ? 'checked' : ''}}>
            <label class='form-check-label' for='send_bills'>Send Invoices</label>
        </div>
    </div>
</form>
