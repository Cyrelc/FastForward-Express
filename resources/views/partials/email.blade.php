@if (!$isPrimary && isset($email))
    <div class="input-group">

    @if ($email->email_address_id === -2)
        <input type="hidden" name="em-action-add-{{$email->contact_id}}" value="add" />
    @elseif (isset($email->email_address_id))
        <input type="hidden" name="{{ $prefix }}-id" value="{{$email->email_address_id}}" />
    @endif
@endif

@if (isset($email) && isset($email->email_address_id))
    <input type="hidden" name="{{ $prefix }}-id" value="{{$email->email_address_id}}" />
@endif
<input type='email' class='form-control contact-body' id='{{$prefix}}' name='{{$prefix}}' placeholder='{{ $placeholder }}' value="{{isset($email) ? $email->email : ''}}"/>

@if(!$isPrimary && isset($email) && isset($email->email_address_id))
    <span class="input-group-btn">
        <button type="button" {!! $email->email_address_id === -2 ? 'data-new="true"' : '' !!} onclick="deleteInputs(this, 'em', '{{$email->email_address_id}}')" class="btn btn-danger"><i class="fa fa-trash"></i></button>
    </span>
@endif

@if (!$isPrimary && isset($email))
    </div>
@endif
