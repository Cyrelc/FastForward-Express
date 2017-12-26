@if ($show_address)
    <div class="col-lg-6">
@else
    <div class="col-lg-12">
@endif
    <input type='hidden' name='{{$prefix}}-db-id' value='{{isset($contact->contact_id) ? $contact->contact_id : null}}' />
    <input type='hidden' id='{{$prefix}}-action' name='{{$prefix}}-action' value='{{isset($contact->contact_id) ? "update" : "create"}}' />
    <!--First Name-->
    <div class="col-lg-4 bottom15">
        <input type='text' class='form-control' id='{{$prefix}}-first-name' name='{{$prefix}}-first-name' placeholder='First Name' value='{{$contact->first_name}}' />
    </div>
    <!--Last Name-->
    <div class="col-lg-4 bottom15">
        <input type='text' class='form-control contact-body' id='{{$prefix}}-last-name' name='{{$prefix}}-last-name' placeholder='Last Name' value="{{$contact->last_name}}"/>
    </div>

    <!--Position-->
    <div class="col-lg-4 bottom15">
        <input type='text' class='form-control contact-body' id='{{$prefix}}-position' name='{{$prefix}}-position' placeholder='Position / Title' value="{{$contact->position}}"/>
    </div>
    <!--Email 1-->
    <div class='col-lg-6 bottom15'>
        @include('partials.email', ['prefix'=> $prefix . '-email1','email'=>(isset($contact) ? $contact->primaryEmail : null), 'isPrimary'=>true, 'placeholder'=> 'Primary Email'])
    </div>
    <!--Email 2-->
    <div class='col-lg-6'>
        @include('partials.email', ['prefix'=> $prefix . '-email2','email'=>(isset($contact) ? $contact->secondaryEmail : null), 'isPrimary'=>false, 'placeholder'=> 'Secondary Email'])
    </div>
    <!--Phone-->
    <div class="col-lg-12 bottom15">
        @include('partials.phone_numbers', ['prefix'=> $prefix . '-phone', 'phoneNumbers'=> $contact->phone_numbers])
    </div>
</div>

<!--Address-->
@if ($show_address)
    <div class="col-lg-6">
        @include('partials.address', ['prefix' => $prefix . '-address', 'address' => isset($contact->address) ? $contact->address : null, 'enabled'=>true])
    </div>
@endif

@if(isset($multi))
    <div class='col-lg-12' id='{{$prefix}}-options' style='text-align: center'>
        <button type='button' id='{{$prefix}}-make-primary' class='btn btn-sm btn-primary' onclick='makePrimary("{{$prefix}}","contact-{{$contact_view_id}}")'><i class='fa fa-star'></i>&nbsp&nbspMake Primary</button>
        <button type='button' id='{{$prefix}}-delete' class='btn btn-sm btn-danger' onclick='deleteContact("{{$prefix}}","contact-{{$contact_view_id}}")'><i class='fa fa-trash'></i>&nbsp&nbspDelete</button>
    </div>
@endif
