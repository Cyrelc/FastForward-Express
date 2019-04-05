$(document).ready(function() {
    $('#users_table').DataTable({
        ajax: {'url':'/users/getAccountUsers/' + $('#account-id').val(), 'dataSrc':''},
        dom: 'f<"columnVis"B>lrtip',
        buttons: [{extend: 'print', exportOptions: {columns: ':visible'}}, 'colvis', {text: 'Add User', action: function(){addUser()}}],
        pageLength: 50,
        order: [1, 'asc'],
        createdRow: function (row, data, index) {
            var deleteButton = '<a class="fa fa-trash-alt btn btn-danger btn-xs" title="Delete User" data-toggle="modal" data-target="#delete_user_modal" onclick="prepDeleteUser(' + data.contact_id + ')" />';
            var editUser = '<a class="btn btn-warning btn-xs" title="Edit User" onclick="editUser(' + data.contact_id + ')" ><i class="fas fa-user-edit"></i></a>';

            var userBadge = data.user_id == null ? '' : '<span class="badge badge-pill badge-primary">User</span>';
            var primaryBadge = data.is_primary ? '<span class="badge badge-pill"><i class="fas fa-star"></i>&nbsp&nbspPrimary</span>' : '';
            $('.actions', row).html('<div class="hover-div" >' + deleteButton + editUser + '</div>');
            $('.roles', row).html(userBadge + primaryBadge);
        },
        columns: [
            {className:'actions', orderable: false, data: null, defaultContent: '', colvis: false, width:'100px'},
            {'data': 'user_id', visible: false},
            {'data': 'name'},
            {'data': 'primary_email'},
            {'data': 'primary_phone'},
            {'data': 'position'},
            {className:'roles', orderable: false, data:null}
        ]
    })
});

function addUser() {
    $.ajax({
        type: "GET",
        url: '/users/createAccountUser/' + $('#account-id').val(),
        'success' : function(results) {
            $('#edit_user_modal').html(results);
            $('select').selectpicker();
            cleave();
            $('#edit_user_modal').modal('toggle');
        },
        'error': function(response){handleErrorResponse(response)}
    })
}

function prepDeleteUser(contact_id) {
    $('#delete_user_form #contact_id').val(contact_id);
}

function deleteUser() {
    var data = $('#delete_user_form').serialize();

    $.ajax({
        url: '/users/deleteAccountUser',
        type : 'POST',
        data: data,
        'success': function(response) {
            toastr.clear();
            toastr.success('User successfully deleted', 'Success', {
                'progressBar': true,
                'showDuration' : 500,
            });
            $('#delete_user_modal').toggle();
        },
        'error': function(response){handleErrorResponse(response)}
    });
}

function editUser(contactId) {
    $.ajax({
        type: "GET",
        url: '/users/editAccountUser/' + contactId,
        'success' : function(results) {
            $('#edit_user_modal').html(results);
            $('select').selectpicker();
            cleave();
            $('#edit_user_modal').modal('toggle');
        },
        'error': function(response){handleErrorResponse(response)}
    })
}

