$(document).ready(function() {
    $('#activity_log_table').DataTable({
        dom: 'f<"columnVis"B>lrtip',
        buttons: [{extend: 'print', exportOptions: {colums: ':visible'}}, 'colvis'],
        pageLength: 50,
        order: [0, 'desc'],
        columnDefs : [5, 'width: 200px']
    });
    //currently no reason to do this as async
    // var path = window.location.pathname.split('/');

    // $('#activity_log_table').DataTable({
    //     ajax: {'url': window.location.origin + '/' + path[1] + '/getActivityLog/' + path[3], 'dataSrc':''},
    //     dom: 'f<"columnVis"B>lrtip',
    //     buttons: [{extend: 'print', exportOptions: {colums: ':visible'}}, 'colvis'],
    //     pageLength: 50,
    //     order: [0, 'desc'],
    //     columns: [
    //         {'data': 'updated_at'},
    //         {'data': 'subject_type'},
    //         {'data': 'subject_id'},
    //         {'data': 'user_name'},
    //         {'data': 'description'},
    //         {'data': 'properties'},
    //     ]
    // })
})

