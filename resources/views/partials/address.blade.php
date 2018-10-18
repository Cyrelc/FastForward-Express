<div class="form-group bottom15" id="{{$prefix}}-div">
    <!-- Search -->
    <div class='col-md-12 bottom15'>
        <div class='input-group'>
            <span class='input-group-addon'>Address Search/Autocomplete: </span>
            <input id='{{$prefix}}_location_search' type='text' data-div='{{$prefix}}' class='form-control' placeholder="Enter an Address" onFocus='geolocate("{{$prefix}}")'></input>
        </div>
    </div>

    <!--Address ID-->
    <input type="hidden" name="{{$prefix}}-id" value="{{isset($address) ? $address->address_id : ''}}" />

    <!--Name-->
    <div class="col-lg-12 bottom15">
        <input type='text' id="{{$prefix}}-name" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-name' placeholder="Address Name" value="{{isset($address) ? $address->name : ''}}" />
    </div>

    <!--Steet-->
    <div class="col-lg-6">
        <input type='text' id="{{$prefix}}-street" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-street' placeholder="Address Line 1"  value="{{isset($address) ? $address->street : ''}}"/>
    </div>

    <!--Street 2-->
    <div class="col-lg-6 bottom15">
        <input type='text' id="{{$prefix}}-street2" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-street2' placeholder="Address Line 2" value="{{isset($address) ? $address->street2 : ''}}" />
    </div>

    <!--City-->
    <div class="col-lg-6">
        <input type='text' id="{{$prefix}}-city" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-city' placeholder="City" value="{{isset($address) ? $address->city : ''}}" />
    </div>

    <!--Province -->
    <div class="col-lg-6 bottom15">
        <input type='text' id="{{$prefix}}-province" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-state-province' placeholder="Province/State" value="{{isset($address) ? $address->state_province : ''}}" />
    </div>

    <!--Zip-->
    <div class="col-lg-6 bottom15">
        <input type='text' id="{{$prefix}}-zip" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-zip-postal' placeholder="Postal/Zip Code"  value="{{isset($address) ? $address->zip_postal : ''}}" />
    </div>

    <!--Country-->
    <div class="col-lg-6">
        <input type='text' id="{{$prefix}}-country" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-country' placeholder="Country" value="{{isset($address) ? $address->country : ''}}" />
    </div>
</div>