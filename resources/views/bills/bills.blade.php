@extends ('layouts.tables')

@section ('variables')

<?php 
	$columns = array('Number', 'Date', 'Description', 'Customer', 'Amount', 'Taxes', 'Driver');
?>

@endsection

@section ('script')

<script type='text/javascript'>

	function childRow() {
		return '<button class='edit-button'><a href=''><i class='fa fa-edit'></i></a></button>' +
				'<button class='delete-button'><a href=''><i class='fa fa-trash'></i></a></button>';
	}

</script>

@parent

@endsection

@section ('navBar')

<table>
	<button class='navButton btn-primary fa'><i class='fa-icon-plus'></i>Create New Bill</button>
	<button class='navButton btn-primary'>Edit Bill</button>
	<button class='navButton btn-primary'>Eat IceCream</button>
</table>

@endsection
