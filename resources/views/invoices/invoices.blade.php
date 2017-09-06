@extends ('layouts.tables')

@section ('variables')

<?php
	$columns = [];
//	$columns = ['Invoice Id', 'Account', 'Invoice Date', 'First Bill', 'Last Bill';
	$variables = [];

	$tableConfig = [
		'table' => 'invoices',
		'editPath' => 'invoices/view/',
		// 'actionPath' => 'bills/action',
		'id_col' => 1,
		'name_col' => 3
	];
?>

@endsection

@section ('script')

@parent

@endsection

@section('navBar')

@endsection
