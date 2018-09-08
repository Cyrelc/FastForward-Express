<script type='text/javascript' src='{{URL::to('/')}}/js/bills/basic.js?{{config('view.version')}}'></script>

<div class="clearfix">
<!-- Pickup/Delivery -->
    <div class='col-md-12'>
<!-- Pickup -->
        <div class='col-md-6'>
            @include('partials.pickup_delivery', ['prefix' => 'pickup', 'title' => 'Pickup', 'address' => $model->pickupAddress, 'account_id' => $model->bill->pickup_account_id, 'date' => $model->bill->pickup_date_scheduled])
        </div>
<!-- Delivery -->
        <div class='col-md-6'>
            @include('partials.pickup_delivery', ['prefix' => 'delivery', 'title' => 'Delivery', 'address' => $model->deliveryAddress, 'account_id' => $model->bill->delivery_account_id, 'date' => $model->bill->delivery_date_scheduled])
        </div>
    </div>
<!-- Delivery Type -->
    <div class="col-lg-12 bottom15">
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
                @if($model->packages == null)
                    <script type='text/javascript'>addPackage()</script>
                @else
                    @foreach($model->packages as $package)
                        <script type="text/javascript">addPackage({{$package->weight}}, {{$package->length}}, {{$package->width}}, {{$package->height}}, {{$package->package_id}});</script>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
<!-- Description -->
    <div class="col-lg-12 bottom15">
        <label for="description">Description: </label>
        <textarea class="form-control" rows="5" name="description" placeholder="Any details pertaining to this bill">{{$model->bill->description}}</textarea>
    </div>
</div>
