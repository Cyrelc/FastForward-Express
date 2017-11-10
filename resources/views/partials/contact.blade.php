    <script type="text/javascript">
        $(document).ready(function(){
            phoneInput("{{$prefix}}-phone1");
            phoneInput("{{$prefix}}-phone2");
        });
    </script>

    @if ($show_address)
        <div class="col-lg-6">
    @else
        <div class="col-lg-12">
    @endif
    @if(isset($contact) && $contact->contact_id !== 0)
        @if(isset($multi) && $multi=="true")
            <input type="hidden" name="contact-id-{{$contact->contact_id}}" />
        @else
            <input type="hidden" name="id-for-{{$prefix}}" value="{{$contact->contact_id}}" />
        @endif

        @if (isset($contact->is_new) && $contact->is_new == 'true')
            <input type="hidden" name="contact-action-new[]" value="{{$contact->contact_id}}" />
            @if(isset($multi) && $multi=="true")
                <input type="hidden" name="contact-id-{{$contact->contact_id}}" />
            @endif
        @else
            <input type="hidden" name="contact-action-update[]" value="{{$contact->contact_id}}" />
            @if(isset($multi) && $multi=="true")
                <input type="hidden" name="contact-id-{{$contact->contact_id}}" />
            @endif
        @endif
    @endif

    <input type="hidden" data-contact-id="true" value="{{ isset($contact) ? ($contact->contact_id > 0 ? $contact->contact_id : '-1') : '-1' }}" />

    <!--First Name-->
    <div class="col-lg-4 bottom15">
        <input type='text' class='form-control contact-body' id='{{$prefix}}-first-name' name='{{ $prefix }}-first-name' placeholder='First Name' value="{{isset($contact) ? $contact->first_name : ''}}"/>
    </div>

    <!--Last Name-->
    <div class="col-lg-4 bottom15">
        <input type='text' class='form-control contact-body' id='{{$prefix}}-last-name' name='{{ $prefix }}-last-name' placeholder='Last Name' value="{{isset($contact) ? $contact->last_name : ''}}"/>
    </div>

    <!--Position-->
    <div class="col-lg-4 bottom15">
        <input type='text' class='form-control contact-body' id='{{$prefix}}-position' name='{{$prefix}}-position' placeholder='Position / Title' value="{{isset($contact) ? $contact->position : ''}}"/>
    </div>

    <!--Phone 1-->
    <div class="col-lg-7 bottom15">
        @include('partials.phone_number', ['prefix'=>$prefix . '-phone1', 'phone'=>(isset($contact) ? $contact->primaryPhone : null), 'isPrimary'=>true, 'placeholder'=> 'Primary Phone'])
    </div>

    <!--Email 1-->
    <div class='col-lg-5'>
        @include('partials.email', ['prefix'=>$prefix . '-email1','email'=>(isset($contact) ? $contact->primaryEmail : null), 'isPrimary'=>true, 'placeholder'=> 'Primary Email'])
    </div>

    <!--Phone 2-->
    <div class='col-lg-7 bottom15'>
        @include('partials.phone_number', ['prefix'=>$prefix . '-phone2', 'phone'=>(isset($contact) ? $contact->secondaryPhone : null), 'isPrimary'=>false, 'placeholder'=> 'Secondary Phone'])
    </div>

    <!--Email 2-->
    <div class='col-lg-5'>
        @include('partials.email', ['prefix'=>$prefix . '-email2','email'=>(isset($contact) ? $contact->secondaryEmail : null), 'isPrimary'=>false, 'placeholder'=> 'Secondary Email'])
    </div>

    <!--Multi stuff-->
    @if (isset($multi) && $multi)
        <div class="col-lg-12 text-center">
            <ul class="nav nav-pills">
                @if(!isset($contact) || $contact->contact_id === 0)
                    <li class="text-center" title="Save">
                        <a href="javascript:saveScContact('{{$multi_div_prefix}}', '{{$prefix}}', {{ $show_address == 1 ? 'true' : 'false' }})"><i class="fa fa-plus"></i></a>
                    </li>
                @endif

                <li title="Delete">
                    @if (isset($contact) && isset($contact->contact_id))
                        <a href="javascript:removeSc('{{$contact->contact_id}}')"><i class="fa fa-trash"></i></a>
                    @else
                        <a href="javascript:clearScForm('{{$prefix}}', {{ $show_address == 1 ? 'true' : 'false' }})"><i class="fa fa-trash"></i></a>
                    @endif
                </li>

                @if(isset($contact) && (!isset($contact->is_primary) || $contact->is_primary === false))
                    <li title="Make Primary">
                        <a href="javascript:void(0);" onclick="makePrimary(this)"><i class="fa fa-star"></i></a>
                    </li>
                @endif
            </ul>
        </div>
    @endif
    </div>

    <!--Address-->
    @if ($show_address)
        <div class="col-lg-6">
            @include('partials.address', ['prefix' => $prefix . '-address', 'address' => isset($contact->address) ? $contact->address : null, 'enabled'=>true])
        </div>
    @endif
