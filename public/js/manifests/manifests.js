$(document).ready(function() {

    var table = $('#table').DataTable( {
        ajax: {url:'/manifests/buildTable', dataSrc:''},
        dom: 'f<"columnVis"B>lrtip',
        buttons: [{extend: 'print', exportOptions: {columns: ':visible'}},'colvis'],
        columnDefs: [{'sWidth':'30px', 'aTargets':[1]}],
        pageLength: 50,
        order: [2, 'desc'],
        createdRow: function (row, data, index) {
//TODO - currently no function to delete a manifest. The commented out delete button will be correct, when this function exists            
//            var deleteButton = '<a class="fa fa-trash-alt btn btn-danger btn-xs" title="Delete Manifest" data-toggle="modal" data-target="#delete_modal" onclick="deleteManifest(' + data.manifest_id + ')" />';
            var deleteButton = '<a class="fa fa-trash-alt btn btn-danger btn-xs" title="Delete Manifest" onclick="deleteManifest(' + data.manifest_id + ')" />';
            $('.manifest_id', row).html('<a href="/manifests/view/' + data.manifest_id + '" >' + data.manifest_id + '</a>');
            $('td', row).eq(0).html('<input class="manifestSelect" type="checkbox" name="checkboxes[' + data.manifest_id + ']" />');
            $actiontd = $('td', row).eq(1);
            $actiontd.html('<div class="hover-div" >' + deleteButton + '</div>');
            $('td', row).eq(4).html('<a href="/employees/edit/' + data.employee_id + '" >' + data.employee_name + '</a>');
        },
        columns: [
            {className:'select', orderable: false, data: null, defaultContent: ''},
            {className:'actions', orderable: false, data: null, defaultContent: ''},
            {data: 'manifest_id', className: 'manifest_id'},
            {data: 'driver_id'},
            {data: 'employee_name'},
            {data: 'date_run'},
            {data: 'start_date'},
            {data: 'end_date'},
            {data: 'bill_count'}
        ]
    });
});

function printMass(){
    var data = $('.manifestSelect').serialize();
    if(data == '') {
        toastr.error('No Manifests were selected for download', 'Error');
        return;
    }
    $('#downloadModal').modal('show');

    $.ajax({
        url: '/manifests/printMass',
        type: 'post',
        data: data,
        success: function(response) {
            $('#downloadModal').modal('hide');
            toastr.clear();
            toastr.success('Your download will begin momentarily', 'Success');
            window.location = '/manifests/download/' + response;
        },
        error: function(response){
            $('#downloadModal').modal('hide');
            var errorText = '';
            for(var key in response.responseJSON){
                errorText += response.responseJSON[key][0] + '</br>';
            }
            toastr.clear();
            toastr.error(errorText, 'Errors', {'timeOut': 0, 'extendedTImeout': 0});
        }
    });
}

function selectAll(selectAllCheckbox) {
    $('.manifestSelect').each(function() {
        $(this).prop('checked', $(selectAllCheckbox).prop('checked'));
    });
}

function deleteManifest(id) {
    $('#delete_modal #delete_button').attr('href', '/manifests/delete/' + id);
}
