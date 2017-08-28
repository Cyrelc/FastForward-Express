<div class="input-group">
    @if(isset($phone) &&  isset($phone->phone_number_id))
        @if ($phone->phone_number_id === -2)
            <input type="hidden" name="pn-action-add-{{$phone->contact_id}}" value="add" />
        @elseif (isset($phone->phone_number_id))
            <input type="hidden" name="{{ $prefix }}-id" value="{{$phone->phone_number_id}}" />
        @endif
    @endif
    <input type="tel" id="{{$prefix}}" name='{{ $prefix }}' class='form-control contact-body' placeholder='{{$placeholder}}' value="{{isset($phone) ? $phone->phone_number : ''}}"/>
    <span class="input-group-addon">Ext.</span>
    <input type="tel" id="{{$prefix}}-ext" class='form-control contact-body' name='{{ $prefix }}-ext' placeholder='Extension' value="{{isset($phone) ? $phone->extension_number : ''}}"/>
</div>
