<script type="text/javascript">
    $(document).ready(function(){
        phoneInput("{{$prefix}}-phone1");
        phoneInput("{{$prefix}}-phone2");
    });
</script>
@php @endphp
@if ($show_address)
    <div class="col-lg-6">
@else
    <div class="col-lg-12">
@endif

    @if(isset($contact) && $contact->contact_id > 0)
        <input type="hidden" name="contact-id-{{$contact->contact_id}}" />
        <input type="hidden" name="contact-action-update[]" value="{{$contact->contact_id}}" />
    @endif

    <div class="col-lg-6 bottom15">
        <input type='text' class='form-control contact-body' id='{{$prefix}}-first-name' {{ isset($contact) ? 'name=contact-' . $contact->contact_id . '-first-name' : '' }} placeholder='First Name' value="{{isset($contact) ? $contact->first_name : ''}}"/>
    </div>
    <div class="col-lg-6 bottom15">
        <input type='text' class='form-control contact-body' id='{{$prefix}}-last-name' {{ isset($contact) ? 'name=contact-' . $contact->contact_id . '-last-name' : '' }} placeholder='Last Name' value="{{isset($contact) ? $contact->last_name : ''}}"/>
    </div>
    <div class="col-lg-7 bottom15">
        <div class="input-group">
            @if(isset($contact) &&  isset($contact->primaryPhone->phone_number_id))
                <input type="hidden" name="contact-{{ $contact->contact_id }}-phone1-id" value="{{$contact->primaryPhone->phone_number_id}}" />
            @endif
            <input type="tel" id="{{$prefix}}-phone1" {!! isset($contact) ? 'name="contact-' . $contact->contact_id . '-phone1"' : '' !!} class='form-control contact-body' placeholder='Primary Phone' value="{{isset($contact) ? $contact->primaryPhone->phone_number : ''}}"/>
            <span class="input-group-addon">Ext.</span>
            <input type="tel" id="{{$prefix}}-phone1-ext" class='form-control contact-body' {!! isset($contact) ? 'name=contact-' . $contact->contact_id . '-phone1-ext' : '' !!} placeholder='Extension' value="{{isset($contact) ? $contact->primaryPhone->extension_number : ''}}"/>
        </div>
    </div>
    <div class='col-lg-5'>
        @if (isset($contact) && isset($contact->primaryEmail->email_address_id))
            <input type="hidden" name="contact-{{ $contact->contact_id }}-email1-id" value="{{$contact->primaryEmail->email_address_id}}" />
        @endif
        <input type='email' class='form-control contact-body' id='{{$prefix}}-email1' {!! isset($contact) ? 'name="contact-' . $contact->contact_id . '-email1"' : '' !!} placeholder='Primary Email' value="{{isset($contact) ? $contact->primaryEmail->email : ''}}"/>
    </div>
    <div class='col-lg-7 bottom15'>
        <div class="input-group">
            @if(isset($contact) && isset($contact->secondaryPhone))
                @if ($contact->secondaryPhone->phone_number_id === -2)
                    <input type="hidden" name="pn-action-add-{{$contact->contact_id}}" value="add" />
                @elseif (isset($contact->secondaryPhone->phone_number_id))
                    <input type="hidden" name="contact-{{ $contact->contact_id }}-phone2-id" value="{{$contact->secondaryPhone->phone_number_id}}" />
                @endif
            @endif
            <input type="tel" id="{{$prefix}}-phone2" class='form-control contact-body' {!! isset($contact) && isset($contact->secondaryPhone) ? 'name="contact-' . $contact->contact_id . '-phone2"' : '' !!} placeholder='Secondary Phone' value="{{isset($contact) && isset($contact->secondaryPhone) ? $contact->secondaryPhone->phone_number : ''}}"/>
            <span class="input-group-addon">Ext.</span>
            <input type="tel" id="{{$prefix}}-phone2-ext" class='form-control contact-body' {!! isset($contact) && isset($contact->secondaryPhone) ? 'name="contact-' . $contact->contact_id . '-phone2-ext"' : '' !!} placeholder='Extension' value="{{isset($contact) && isset($contact->secondaryPhone) ? $contact->secondaryPhone->extension_number : ''}}"/>
            @if(isset($contact) && isset($contact->secondaryPhone))
                <span class="input-group-btn">
                    <button type="button" {!!$contact->secondaryPhone->phone_number_id === -2 ? 'data-new="true"' : ''!!} onclick="deleteInputs(this, 'pn', '{{$contact->secondaryPhone->phone_number_id}}')" class="btn btn-danger"><i class="fa fa-trash"></i></button>
                </span>
            @endif
        </div>
    </div>

    <div class='col-lg-5'>
        @if(isset($contact) && isset($contact->secondaryEmail))
            <div class="input-group">
            @if ($contact->secondaryEmail->email_address_id === -2)
                <input type="hidden" name="em-action-add-{{$contact->contact_id}}" value="add" />
            @elseif (isset($contact->secondaryEmail->email_address_id))
                <input type="hidden" name="contact-{{$contact->contact_id}}-email2-id" value="{{$contact->secondaryEmail->email_address_id}}" />
            @endif
        @endif
        <input type='email' class='form-control contact-body' id='{{$prefix}}-email2' {!! isset($contact) && isset($contact->secondaryEmail) ? 'name="contact-' . $contact->contact_id . '-email2"' : '' !!} placeholder='Secondary Email' value="{{isset($contact) && isset($contact->secondaryEmail) ? $contact->secondaryEmail->email : ''}}"/>
        @if(isset($contact) && isset($contact->secondaryEmail))
            <span class="input-group-btn">
                <button type="button" {!! $contact->secondaryEmail->email_address_id === -2 ? 'data-new="true"' : '' !!} onclick="deleteInputs(this, 'em', '{{$contact->secondaryEmail->email_address_id}}')" class="btn btn-danger"><i class="fa fa-trash"></i></button>
            </span>
            </div>
        @endif
    </div>

    @if (isset($multi) && $multi)
        <div class="col-lg-12 text-center">
            <ul class="nav nav-pills">
                <li class="text-center" title="Save">
                    <a href="javascript:saveScContact('{{$prefix}}')"><i class="fa fa-save"></i></a>
                </li>
                <li title="Delete">
                    <a href="javascript:clearScForm()"><i class="fa fa-trash"></i></a>
                </li>

                @if(isset($contact) && (!isset($contact->is_primary) || $contact->is_primary === false))
                    <li title="Make Primary">
                        <a href="javascript:makePrimary(this)"><i class="fa fa-star"></i></a>
                    </li>
                @endif
            </ul>
        </div>
    @endif
    </div>
@if ($show_address)
    <div class="col-lg-6">
        @include('partials.address', ['prefix' => $prefix . '-address', 'address' => isset($address) ? $address : null, 'enabled'=>true])
    </div>
@endif
