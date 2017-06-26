<script src="{{URL::to('/')}}/js/contact.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
        @php
            foreach($contacts as $c) {
                $id = $c->contact_id;
                if (isset($c->delete) && $c->delete === true) {
                    echo 'addDeleted("' . $id . '");';
                } else {
                    $fName = addslashes($c->first_name);
                    $lName = addslashes($c->last_name);
                    $ppnId = $c->primaryPhone->phone_number_id;
                    $ppn = $c->primaryPhone->phone_number;
                    $ppnExt = $c->primaryPhone->extension_number;
                    $emId = $c->primaryEmail->email_address_id;
                    $em = $c->primaryEmail->email;

                    $spnId = $spn = $spnExt = $em2Id = $em2 = null;
                    if (isset($c->secondaryPhone)) {
                        if ($c->secondaryPhone->is_new === true)
                            $spnId = -2;
                        else
                            $spnId = $c->secondaryPhone->phone_number_id;

                        $spn = $c->secondaryPhone->phone_number;
                        $spnExt = $c->secondaryPhone->extension_number;
                    }

                    if (isset($c->secondaryEmail)) {
                        if ($c->secondaryEmail->is_new === true)
                            $em2Id = -2;
                        else
                            $em2Id = $c->secondaryEmail->email_address_id;

                        $em2 = $c->secondaryEmail->email;
                    }
                }
            }
        @endphp
    });
</script>

    <div class='col-lg-12'>
        <div class="panel panel-default" id="{{$prefix}}-contacts">
            <div class='panel-heading'>
                <h3 class='panel-title'>{{$title}}</h3>
            </div>

            <div class="panel-body">
                <div class='col-lg-2'>
                    <ul id="{{$prefix}}-contact-tabs" class="tab nav nav-pills nav-stacked bottom15" role="tablist">
                        <li><a href="#{{$prefix}}-new-contact" aria-controls="profile" role="tab" data-toggle="tab" class="active"><i class="fa fa-plus-circle"></i> Add New</a></li>

                        @foreach($contacts as $c)
                            <li class="{{ $c->is_primary == '1' ? 'active' : '' }}" role='presentation'><a data-id='{{ $c->contact_id  }}' href='#{{ $c->contact_id }}-panel' aria-controls='{{ $c->contact_id }}' role='tab' data-toggle='tab'>{!! $c->is_primary == '1' ? '<i class="fa fa-star"></i>' : '' !!} {{ $c->first_name }} {{ $c->last_name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Contact Tab panes -->
                <div class="col-lg-10">
                    <div class="tab-content" id="{{$prefix}}-contact-bodies">
                        <div role="tabpanel" class="tab-pane" id="{{$prefix}}-new-contact">
                            @include('partials.contact', ['multi' => true, 'prefix' => $prefix, 'showAddress' => true])
                        </div>

                        @foreach($contacts as $c)
                            <div role="tabpanel" class="tab-pane {{$c->is_primary == '1' ? 'active' : ''}}" id="{{$c->contact_id}}-panel">
                                @include('partials.contact', ['multi' => true, 'prefix' => 'contact-' . $c->contact_id, 'contact' => $c, 'showAddress' => false])

                                @if ($c->is_primary === true)
                                    <input type="hidden" name="contact-action-change-primary" value="{{$c->contact_id}}" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
