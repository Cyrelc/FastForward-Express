<script type="text/javascript">
    $(document).ready(function(){
        zipInput("{{$prefix}}-zip");
    });
</script>

<div class="form-group bottom15" id="{{$prefix}}-div">
    <input type="hidden" name="{{$prefix}}-id" value="{{isset($address) ? $address->address_id : ''}}" />
    <div class="col-lg-6">
        <input type='text' {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-street' placeholder="Address Line 1"  value="{{isset($address) ? $address->street : ''}}"/>
    </div>
    <div class="col-lg-6 bottom15">
        <input type='text' id="{{$prefix}}-zip" {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-zip-postal' placeholder="Postal/Zip Code"  value="{{isset($address) ? $address->zip_postal : ''}}" />
    </div>
    <div class="col-lg-6 bottom15">
        <input type='text' {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-street2' placeholder="Address Line 2" value="{{isset($address) ? $address->street2 : ''}}" />
    </div>
    <div class="col-lg-6 bottom15">
        <input type='text' {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-state-province' placeholder="Province/State" value="{{isset($address) ? $address->state_province : ''}}" />
    </div>
    <div class="col-lg-6">
        <input type='text' {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-city' placeholder="City" value="{{isset($address) ? $address->city : ''}}" />
    </div>
    <div class="col-lg-6">
        <input type='text' {{$enabled ? '' : 'disabled'}} class='form-control' name='{{$prefix}}-country' placeholder="Country" value="{{isset($address) ? $address->country : ''}}" />
    </div>
</div>