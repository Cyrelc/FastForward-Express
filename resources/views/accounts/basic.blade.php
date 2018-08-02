<!--Basic Information Panel-->
<input type="hidden" id='account-id' name="account-id" value="{{ $model->account->account_id }}" />

<form id='account_basic'>
    <div class="clearfix">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Basic Info</h3>
                </div>
                <div class='panel-body'>
<!--Account Name-->
                    <div class="col-lg-4 bottom15">
                        <div class='input-group'>
                            <span class='input-group-addon'>Name</span>
                            <input type='text' class="form-control" id='name' name="name" placeholder="Company Name" value="{{$model->account->name}}" />
                        </div>
                    </div>
<!--Invoice Interval-->
                    <div class="col-lg-4 bottom15">
                        <div class='input-group'>
                            <span class='input-group-addon'>Invoice Interval</span>
                            <select class='form-control' name="invoice-interval" placeholder="Select Invoice Interval">
                                @foreach ($model->invoice_intervals as $ii)
                                    @if (isset($model->account->invoice_interval) && $ii->value ==$model->account->invoice_interval)
                                        <option selected value="{{$ii->value}}">{{$ii->name}}</option>
                                    @else
                                        <option value="{{$ii->value}}">{{$ii->name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
<!--Custom Field-->
                    <div class="col-lg-4 bottom15" id="custom-div">
                        <div class="input-group">
                            <input type='text' class="form-control" name='custom-tracker' placeholder="Tracking Field Name (optional)" value="{{$model->account->uses_custom_field == 1 ? $model->account->custom_field : ""}}"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<!-- Addresses -->
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Delivery Address</h3>
                </div>
                <div class="panel-body">
                    @include('partials.address', ['prefix' => 'delivery', 'address' => $model->deliveryAddress, 'enabled' => true])
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading" style="height: 39px;">
                    <h3 class="panel-title">
                        <label style="font-weight: normal;">
                            <input type='checkbox' id='billing-address' name='billing-address' {{isset($model->billingAddress->address_id) ? 'checked' : ''}} onclick='switchDiv(this, "billing-div")'/> Billing Address
                        </label>
                    </h3>
                </div>
                <div class="panel-body">
                    @include('partials.address', ['prefix' => 'billing', 'address' => $model->billingAddress, 'enabled' => isset($model->billingAddress->address_id)])
                </div>
            </div>
        </div>
<!-- Contacts Panel -->
        @include('partials.contacts', ['contacts' => $model->account->contacts, 'show_address' => false, 'prefix' => 'account', 'title' => 'Contacts'])
    </div>
</form>
