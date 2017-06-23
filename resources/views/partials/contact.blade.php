<script type="text/javascript">
    $(document).ready(function(){
        phoneInput("phone1");
        phoneInput("phone2");
    });
</script>

@if (isset($show_address) && $show_address)
    <div class="col-lg-6">
@endif
    <div class="col-lg-6 bottom15">
        <input type='text' class='form-control contact-body' id='first-name' placeholder='First Name'/>
    </div>
    <div class="col-lg-6 bottom15">
        <input type='text' class='form-control contact-body' id='last-name' placeholder='Last Name'/>
    </div>
    <div class="col-lg-6 bottom15">
        <div class="input-group">
            <input type="tel" id="phone1" class='form-control contact-body' placeholder='Primary Phone'/>
            <span class="input-group-addon">Ext.</span>
            <input type="tel" id="phone1-ext" class='form-control contact-body' placeholder='Extension'/>
        </div>
    </div>
    <div class='col-lg-6 bottom15'>
        <div class="input-group">
            <input type="tel" id="phone2" class='form-control contact-body' placeholder='Secondary Phone'/>
            <span class="input-group-addon">Ext.</span>
            <input type="tel" id="phone2-ext" class='form-control contact-body' placeholder='Extension'/>
        </div>
    </div>
    <div class='col-lg-6'>
        <input type='email' class='form-control contact-body' id='email1' placeholder='Primary Email'/>
    </div>
    <div class='col-lg-6'>
        <input type='email' class='form-control contact-body' id='email2' placeholder='Secondary Email'/>
    </div>

    @if (isset($multi) && $multi)
        <div class="text-center">
            <ul class="nav nav-pills">
                <li class="text-center" title="Save">
                    <a href="javascript:saveScContact()"><i class="fa fa-save"></i></a>
                </li>
                <li title="Delete">
                    <a href="javascript:clearScForm()"><i class="fa fa-trash"></i></a>
                </li>
            </ul>
        </div>
    @endif

    @if (isset($show_address) && $show_address)
    </div>
    <div class="col-lg-6">
        @include('partials.address', ['prefix' => $prefix . '-address', 'address' => isset($address) ? $address : null, 'enabled'=>true])
    </div>
@endif
