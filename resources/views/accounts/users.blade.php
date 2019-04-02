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

