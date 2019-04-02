<script type='text/javascript' src='/js/accounts/editUser.js?{{config('view.version')}}'></script>

<div class='modal-dialog modal-lg'>
    <div class='modal-content'>
        <div class='modal-header'>
            <button type='button' class='close' data-dismiss='modal'>&times;</button>
            <h4 class='modal-title'>Edit User</h4>
        </div>
        <div class='modal-body clearfix'>
            <form id='user_form'>
                <input class='hidden' name='account_id' value='{{isset($model->account_id) ? $model->account_id : ''}}' />
                <ul class='nav nav-pills nav-justified'>
                    <li class='active'><a data-toggle='tab' href='#details'>Details</a></li>
                    <li><a data-toggle='tab' href='#permissions'>Permissions</a></li>
                </ul>
                <div class='tab-content'>
                    <div id='details' class="tab-pane fade in active well clearfix">
                        @include('partials.contact', ['show_address' => false, 'contact' => $model->contact])
                    </div>
                    <div id='permissions' class='tab-pane fade well clearfix'>
                        <input type='checkbox' id='active' name='active' class='form-check-input checkbox-lg' />
                        <label class='form-check-label' for='active'>Active</label>
                        <p>SoMe PeRmIsSiOnS sTuFf WiLl Go HeRe</p>
                    </div>
                </div>
            </form>
        </div>
        <div class='modal-footer'>
            <button type='button' class='btn btn-success' onclick='storeUser(this)'><i class='fas fa-save'></i>&nbsp&nbspSave</button>
            <button type='button' class='btn btn-danger' data-dismiss='modal'>Cancel</button>
        </div>
    </div>
</div>

