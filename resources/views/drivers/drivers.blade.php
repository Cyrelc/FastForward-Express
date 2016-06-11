@extends ('layouts.tables')

@section ('variables')

<?php
	use \app\Http\Controllers\DriverController;

	$columns = ;
	$variables = ;
	$contents = ;
	if ($contents['success']){
		$contents = $contents['data'];
	}
?>

@endsection

@section ('script')

<script type='text/javascript'>

	function childRow(details) {
		var data = JSON.parse(details);

		var thisDriver = 'editDriver' + data.number;

		return "<table>" +
				"</table>";
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
