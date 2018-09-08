<script type='text/javascript' src='{{URL::to('/')}}/js/bills/dispatch.js?{{config('view.version')}}'></script>

<!--pickup driver-->
<div class="clearfix">
    <div class="col-lg-8 bottom15">
        <div class="input-group">
            <span class="input-group-addon">Pickup Driver: </span>
            <select id="pickup_driver_id" class="form-control selectpicker" data-live-search='true' name='pickup_driver_id'>
                <option></option>
                @foreach($model->employees as $e)
                    @if((isset($model->bill->pickup_driver_id) && $e->driver->driver_id == $model->bill->pickup_driver_id))
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
<!-- delivery driver -->
    <div class="col-lg-8 bottom15">
        <div class="input-group">
            <span class="input-group-addon">Delivery Driver: </span>
            <select id="delivery_driver_id" class="form-control selectpicker" data-live-search='true' name="delivery_driver_id">
                <option></option>
                @foreach($model->employees as $e)
                    @if((isset($model->bill->delivery_driver_id) && $e->driver->driver_id == $model->bill->delivery_driver_id))
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
<!-- Interliner -->
    <div id="interliner">
        <div class="col-lg-6 bottom15">
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
        <div class="col-lg-3 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Interliner Cost: $</span>
                <input id="interliner_cost" name="interliner_cost" type="number" class="form-control" min="0" value="{{$model->bill->interliner_cost}}" step="0.01" />
            </div>
        </div>
        <div class="col-lg-3 bottom15">
            <div class="input-group">
                <span class="input-group-addon">Interliner Cost to Customer: $</span>
                <input id="interliner_cost_to_customer" name="interliner_cost_to_customer" type="number" class="form-control" min="0" value="{{$model->bill->interliner_cost_to_customer}}" step="0.01" />
            </div>
        </div>
    </div>
</div>
