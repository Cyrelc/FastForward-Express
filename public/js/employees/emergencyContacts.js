$(document).ready(function() {
    $('#emergency_contacts_table').DataTable({
        ajax: {'url':'/employees/getEmergencyContacts/' + $('#employee_id').val(), 'dataSrc':''},
        dom: 'f<"columnVis"B>lrtip',
        buttons: [{extend: 'print', exportOptions: {columns: ':visible'}}, 'colvis', {text: 'Add Emergency Contact', action: function(){addContact()}}],
        pageLength: 50,
        order: [1, 'asc'],
        createdRow: function (row, data, index) {
            var deleteButton = '<a class="fa fa-trash-alt btn btn-danger btn-xs" title="Delete Contact" data-toggle="modal" data-target="#delete_modal" onclick="deleteUser(' + data.contact_id + ')" />';
            var editButton = '<a class="btn btn-warning btn-xs" title="Edit Contact" onclick="editContact(' + data.contact_id + ')" ><i class="fas fa-user-edit"></i></a>';

            $('.actions', row).html('<div class="hover-div" >' + deleteButton + editButton + '</div>');
        },
        columns: [
            {className:'actions', orderable: false, data: null, defaultContent: '', colvis: false, width:'100px'},
            {'data': 'name'},
            {'data': 'primary_email'},
            {'data': 'primary_phone'},
            {'data': 'position'}
        ]
    })
});

function addContact() {
    var employee_id = $('#employee_id').val();
    $.ajax({
        type: 'GET',
        url: '/employees/createEmergencyContact/' + employee_id,
        'success': function(results) {
            $('#edit_contact_modal').html(results);
            $('select').selectpicker();
            cleave();
            $('#edit_contact_modal').modal('toggle');
        },
        'error': function(response){handleErrorResponse(response)}
    })
}

function editContact(contactId) {
    $.ajax({
        type: "GET",
        url: '/employees/editEmergencyContact/' + contactId,
        'success' : function(results) {
            $('#edit_contact_modal').html(results);
            $('select').selectpicker();
            cleave();
            $('#edit_contact_modal').modal('toggle');
        },
        'error': function(response){handleErrorResponse(response)}
    })
}

