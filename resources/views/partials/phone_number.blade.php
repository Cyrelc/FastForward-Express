<input type='hidden' name='{{$prefix}}-db-id' value='{{isset($phone->phone_number_id) ? $phone->phone_number_id : null}}' />
<input type='hidden' id='{{$prefix}}-action' name='{{$prefix}}-action' value='{{isset($phone->phone_number_id) ? "update" : "create"}}' />
<div class="col-lg-1">
    <button type="button" class="btn btn-sm btn-danger" onclick="deletePhone(this, '{{$prefix}}');"><i class="fa fa-trash"></i></button>
</div>
<div class="col-lg-3">
    <select name="{{$prefix}}-type" class="form-control">
        <option></option>
        @foreach($types as $type)
            @if (isset($phone->type) && $type->value == $phone->type)
                <option selected value="{{$type->value}}" >{{$type->name}}</option>
            @else
                <option value="{{$type->value}}" >{{$type->name}}</option>
            @endif
        @endforeach
    </select>
</div>
<div class="col-lg-8">
    <div class="input-group">
        <input type="tel" id='{{$prefix}}-number' name='{{$prefix}}-number' class='form-control contact-body' value="{{isset($phone) ? $phone->phone_number : ''}}"/>
        <span class="input-group-addon">Ext.</span>
        <input type="tel" name="{{$prefix}}-ext" class='form-control contact-body' placeholder='Extension' value="{{isset($phone) ? $phone->extension_number : ''}}"/>
    </div>
</div>

<script type='text/javascript'>$(document).ready(phoneInput('{{$prefix}}-number'));</script>
