$(document).ready(function() {

    var table = $('#table').DataTable( {
        ajax: {url:'/manifests/buildTable', dataSrc:''},
        dom: 'lf<"columnVis"B>rtip',
        buttons: ['colvis'],
        columnDefs: [{'sWidth':'25px', 'aTargets':[0]}],
        pageLength: 50,
        order: [1, 'desc'],
        createdRow: function (row, data, index) {
            var editButton = '<a class="fa fa-edit btn btn-default btn-xs" title="View Manifest" href="/manifests/view/' + data.manifest_id + '" /></button>';
            var deleteButton = '<a class="fa fa-trash btn-danger btn-xs" title="Delete Manifest" onclick="deleteManifest(' + data.manifest_id + '" /></button>';
            $actiontd = $('td', row).eq(0);
            $actiontd.html('<div class="hover-div" >' + editButton + deleteButton + '</div>');
            $('td', row).eq(4).html('<a href="/employees/edit/' + data.employee_id + '" >' + data.employee_name + '</a>');
        },
        columns: [
            {className:'actions', orderable: false, data: null, defaultContent: ''},
            {data: 'manifest_id'},
            {data: 'driver_id'},
            {data: 'employee_id'},
            {data: 'employee_name'},
            {data: 'date_run'},
            {data: 'start_date'},
            {data: 'end_date'},
            {data: 'bill_count'}
        ]
    });
});
