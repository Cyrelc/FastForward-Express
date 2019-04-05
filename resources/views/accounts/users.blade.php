<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/media/js/dataTables.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.print.min.js'></script>
<script type='text/javascript' src='/js/accounts/users.js?{{config('view.version')}}'></script>

<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.bootstrap.min.css'/>
<link rel='stylesheet' type='text/css' href='/css/tables.css' />

<table id='users_table' style='width:100%' class='table table-striped'>
    <thead>
        <tr>
            <td></td>
            <td>User ID</td>
            <td>Name</td>
            <td>Primary Email</td>
            <td>Primary Phone</td>
            <td>Position</td>
            <td>Roles</td>
        </tr>
    </thead>
</table>

<!-- edit modal -->
<div id='edit_user_modal' class='modal fade' role='dialog'>
</div>

<div id='delete_user_modal' class='modal fade' role='dialog'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header'>
                <button type='button' class='close' data-dismiss='modal'>&times;</button>
                <h4 class='modal-title'>Delete User</h4>
            </div>
            <div class='modal-body clearfix'>
                <form id='delete_user_form'>
                    <input type='hidden' id='contact_id' name='contact_id' val='' />
                    <p id='delete_message'>Please confirm deletion of user. <b>This action can not be undone.</b></p>
                </form>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-default' data-dismiss='modal'>Cancel</button>
                <a id='delete_button' type='button' class='btn btn-danger' onclick='deleteUser()'>Delete</a>
            </div>
        </div>
    </div>
</div>

