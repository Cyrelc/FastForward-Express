<div class="form-group bottom15" id="{{$prefix}}-div">
    <!-- Search -->
    <div id='{{$prefix}}_address_search'>
        <div class='col-md-10 bottom15'>
            <div class='input-group'>
                <span class='input-group-addon'>Address Search/Autocomplete: </span>
                <input id='{{$prefix}}_place_search' name='{{$prefix}}-formatted' type='text' data-div='{{$prefix}}' class='form-control' placeholder="Enter an Address" onFocus='geolocate("{{$prefix}}")' value='{{isset($address) ? $address->formatted : ""}}'/>
            </div>
        </div>
        <div class='col-md-2 bottom15'>
            <button type='button' class='btn' data-toggle='collapse' data-target='#{{$prefix}}-details' aria-expanded='false' aria-controls='{{$prefix}}-details'>Details</button>
        </div>
    </div>
    <div id='{{$prefix}}-details' class='collapse'>
        <!--Address ID-->
        <input type="hidden" name="{{$prefix}}-id" value="{{isset($address) ? $address->address_id : ''}}" />
        <input type='hidden' id='{{$prefix}}-lat' name='{{$prefix}}-lat' value='{{isset($address) ? $address->lat : ''}}' />
        <input type='hidden' id='{{$prefix}}-lng' name='{{$prefix}}-lng' value='{{isset($address) ? $address->lng : ''}}' />
        <!--Name-->
        <div class="col-lg-12 bottom15">
            <input type='text' id="{{$prefix}}-name" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-name' placeholder="Address Name" value="{{isset($address) ? $address->name : ''}}" />
        </div>
        <!--Steet-->
        <div class="col-lg-6">
            <input type='text' id="{{$prefix}}-street" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-street' placeholder="Address Line 1"  value="{{isset($address) ? $address->street : ''}}" />
        </div>
        <!--Street 2-->
        <div class="col-lg-6 bottom15">
            <input type='text' id="{{$prefix}}-street2" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-street2' placeholder="Address Line 2 (optional)" value="{{isset($address) ? $address->street2 : ''}}" />
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
    <div id='{{$prefix}}-map' style='height:300px' class='col-md-12'></div>
</div>
