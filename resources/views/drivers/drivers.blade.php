@extends ('layouts.tables')

@section ('variables')

@php
	$contents = $contents->drivers;
	$columns = ['ID', 'Active', 'Driver Number', 'Name', 'Insurance Exp.', 'License Exp.', 'Bills This Month'];
	$variables = [['driver', 'driver_id'], ['driver', 'active'], ['driver', 'driver_number'], ['contact', 'name'], ['driver', 'insurance_expiration'], ['driver', 'license_expiration'], 'bills'];
	$tableConfig = [
		'table' => 'driver',
		'editPath' => 'drivers/edit/',
		'actionPath' => 'drivers/action',
		'id_col' => 1,
		'name_col' => 3
	];
@endphp

@endsection

@section ('script')

<script type='text/javascript'>
    var columnDefs = [{"targets": [ 1, 2 ], "visible": false, "searchable": true}];
    var order = [1, "desc"];

    function dtRowCallback(row, data) {
        console.log(data);
        var id = data[1];
        var name = data[3].replace("'", "\\'");

        var editButton = '<a href="drivers/edit/' + id + '"><i onclick="edit(this)" class="fa fa-edit"></i></a>';
        var delButton = '<a href="javascript:action(' + id + ', \'' + name +'\', \'deactivate\')"><i class="fa fa-trash"></i></a>';
        var activateButton = '<a href="javascript:action(' + id + ', \'' + name +'\', \'activate\')"><i class="fa fa-toggle-on"></i></a>';

        if (data[1] == 0) {
            $(row).addClass('disabled');
            $(row).attr('title', 'Deactivated');
            $(row).find('.hover-div').html(editButton + activateButton);
        } else
            $(row).find('.hover-div').html(editButton + delButton);
    }

</script>

@parent

@endsection

@section ('navBar')
<ul class='nav nav-pills nav-stacked'>
	<li class='navButton'><a href=""><i class='fa fa-plus'></i> Create New Driver</a></li>
	<li class='navButton'><a href=""><i class='fa fa-edit'></i> Edit Driver</a></li>
</ul>
@endsection
