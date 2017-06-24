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

                    echo sprintf("
                        newTabPill(%u, '%s', '%s', %s);
                        newTabBody(%u, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s);",
                        $id, $fName, $lName, $c->is_primary == '1' ? 'true' : 'false', $id, $fName, $lName, $ppnId, $ppn, $ppnExt, $spnId, $spn, $spnExt, $emId, $em, $em2Id, $em2, $c->is_primary == '1' ? 'true' : 'false', isset($c->is_new) ? $c->is_new ? 'true' : 'false' : 'false');
                }
            }
        @endphp
    });
</script>

<div class="row">
    <div class='col-lg-12 panel panel-default' id="contacts">
        <div class='col-lg-12 panel-heading bottom15'>
            <h3 class='panel-title'>{{$title}}</h3>
        </div>

        <div class='col-lg-2'>
            <ul id="contact-tabs" class="tab nav nav-pills nav-stacked bottom15" role="tablist" style="list-style-type:none; padding-top:15px;">
                <li><a href="#new-contact" aria-controls="profile" role="tab" data-toggle="tab" class="active"><i class="fa fa-plus-circle"></i> Add New</a></li>
            </ul>
        </div>
        <!-- Contact Tab panes -->
        <div class="col-lg-10">
            <div class="tab-content" id="contact-bodies">
                <div role="tabpanel" class="tab-pane" id="new-contact">
                    @include('partials.contact', ['multi' => true])
                </div>
            </div>
        </div>
    </div>
</div>