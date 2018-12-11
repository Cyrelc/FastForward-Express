$(document).ready(function() {
    var table = $('#table').DataTable({
        ajax: {url:'/employees/buildTable', dataSrc: ''},
        dom: 'f<"columnVis"B>lrtip',
        buttons: [{extend: 'print', exportOptions: {columns: ':visible'}},'colvis'],
        ColVis: {exclude: [0]},
        pageLength: 50,
        stateSave: true,
        order: [1, 'desc'],
        createdRow: function(row, data, index) {
            var disableButton = '<a class="btn btn-danger btn-xs" title="Disable Employee" onclick="disableEmployee(' + data.employee_id + ')" ><i class="fas fa-lock"></i></a>';
            var enableButton = '<a class="btn btn-success btn-xs" title="Enable Employee" onclick="enableEmployee(' + data.employee_id + ')" ><i class="fas fa-unlock"></i></a>';
            var changePassword = '<a class="btn btn-warning btn-xs" title="Reset Password" data-toggle="modal" data-target="#password_change_modal" onclick="setupChangePassword(' + data.user_id + ',\'' + data.employee_name + '\')" ><i class="fas fa-key"></i></a>';
            if(data.active)
                $('.actions', row).html('<div class="hover-div" >' + disableButton + changePassword + '</div>');
            else
                $('.actions', row).html('<div class="hover-div" >' + enableButton + '</div>');
            var links = [{'db_id_field' : 'employee_id', 'url' : '/employees/edit/', 'db_name_field' : 'employee_id'},
                        {'db_id_field' : 'employee_id', 'url' : '/employees/edit/', 'db_name_field' : 'employee_name'}];
            for(link in links) {
                var cur_row = links[link];
                if(data[cur_row['db_id_field']] != '' && data[cur_row['db_id_field']] != null)
                    $('.' + cur_row['db_id_field'], row).html('<a href="' + cur_row['url'] + data[cur_row['db_id_field']] + '" >' + data[cur_row['db_name_field']] + '</a>');
            }
        },
        columns: [
            {className:'actions', orderable: false, data: null, defaultContent: '', colvis: false},
            {data: 'employee_id'},
            {data: 'employee_number'},
            {data: 'employee_name', className:'employee_id'},
            // {data: 'roles'},
            {data: 'primary_phone'},
            {data: 'company_name', visible: false}
        ]
    })

    $('#bills_advanced_filter').change(function() { //TODO - remove auto refresh when live for clients
        table.ajax.reload();
    });
});

function disableEmployee(employee_id) {
    $.ajax({
        'url': '/employees/disable/' + employee_id,
        'type': 'post',
        'success': function() {

        },
		'error': function(response){handleErrorResponse(response)}
    });
}

function setupChangePassword(user_id, employee_name) {
    $('#password_change_title').html('Change Password for ' + employee_name);
    $('#password_change_submit_button').attr('onclick', 'changePassword(' + user_id + ')');
}

function changePassword(user_id) {
    var data =  $('#password_change_form').serialize();

    $.ajax({
        'url': '/users/changePassword/' + user_id,
        'data': data,
        'type': 'POST',
        'success': function() {
            $('#password_change_form').trigger('reset');
            $('#password_change_modal').modal('hide');
            toastr.success('Password Successfully Changed', 'Success', {
                'progressBar': true,
                'positionClass': 'toast-top-full-width',
                'showDuration': 500
            });
        },
        'error': function(response){handleErrorResponse(response)}
    });
}
