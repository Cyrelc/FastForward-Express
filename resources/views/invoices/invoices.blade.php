@extends ('layouts.tables')

@section ('variables')

<?php
	use \app\Http\Controllers\InvoiceController;

	$columns = [];
	$variables = [];
	$contents = array('success' => false);
	if ($contents['success']){
		$contents = $contents['data'];
	}
?>

@endsection

@section ('script')

<script type='text/javascript'>

	function childRow(details) {
		var data = JSON.parse(details);

		var thisInvoice = 'editInvoice' + data.number;

		return "<table>" +
				"</table>";
	}

</script>

@parent

@endsection

@section('navBar')
<ul class='nav nav-pills nav-stacked'>
	<li class='navButton'><a href=""><i class='fa fa-plus'></i> Create New Invoice</a></li>
	<li class='navButton'><a href=""><i class='fa fa-edit'></i> Edit Invoice</a></li>
</ul>
@endsection
