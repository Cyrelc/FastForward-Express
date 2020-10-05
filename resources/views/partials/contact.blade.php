@if ($show_address)
    <div class="col-lg-6">
@else
    <div class="col-lg-12">
@endif
    <input type='hidden' name='contact_id' value='{{$contact->contact_id}}' />
    <!--First Name-->
    <label for='first_name' class='col-md-2 col-form-label'><h4>First Name</h4></label>
    <div class='col-md-4 bottom15'>
        <input type='text' class='form-control' id='first_name' name='first_name' placeholder='First Name' value='{{$contact->first_name}}' />
    </div>
    <!--Last Name-->
    <label for='last_name' class='col-md-2 col-form-label'><h4>Last Name</h4></label>
    <div class='col-md-4 bottom15'>
        <input type='text' class='form-control' id='last_name' name='last_name' placeholder='Last Name' value='{{$contact->last_name}}' />
    </div>
    <!--Position-->
    <label for='position' class='col-md-4 col-form-label'><h4>Position/Title</h4></label>
    <div class='col-md-8 bottom15'>
        <input type='text' class='form-control' id='position' name='position' placeholder='Position/Title' value='{{$contact->position}}' />
    </div>
</div>
<!--Address-->
@if ($show_address)
    <div class="col-lg-6">
        @include('partials.address', ['prefix' => 'contact-address', 'address' => $contact->address, 'enabled'=>true])
    </div>
@endif
<!--Emails-->
<div class='col-lg-12 bottom15'>
    @include('partials.emails', ['emails' => $contact->emails, 'email_types' => $contact->email_types])
</div>
<!--Phones-->
<div class="col-lg-12 bottom15">
    @include('partials.phone_numbers', ['phone_numbers'=> $contact->phone_numbers, 'phone_types'=> $contact->phone_types])
</div>
    
