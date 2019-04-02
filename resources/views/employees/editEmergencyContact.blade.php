<script type='text/javascript' src='/js/employees/editEmergencyContact.js?{{config('view.version')}}'></script>

<div class='modal-dialog modal-lg'>
    <div class='modal-content'>
        <div class='modal-header'>
            <button type='button' class='close' data-dismiss='modal'>&times;</button>
            <h4 class='modal-title'>Edit Contact</h4>
        </div>
        <div class='modal-body clearfix'>
            <form id='emergency_contact_form'>
                @include('partials.contact', ['contact' => $model, 'show_address' => true])
            </form>
        </div>
        <div class='modal-footer'>
            <button type='button' class='btn btn-success' onclick='storeContact(this)'><i class='fas fa-save'></i>&nbsp&nbspSave</button>
            <button type='button' class='btn btn-danger' data-dismiss='modal'>Cancel</button>
        </div>
    </div>
</div>

