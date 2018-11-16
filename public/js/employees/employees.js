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
            // var deleteButton = '<a class="fa fa-trash-alt btn btn-danger btn-xs" title="Delete Bill" data-toggle="modal" data-target="#delete_modal" onclick="deleteBill(' + data.bill_id + ')" />';
            // if(data.editable)
            //     $('.actions', row).html('<div class="hover-div" >' + deleteButton + '</div>');
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
