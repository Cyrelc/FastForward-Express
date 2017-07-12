@extends ('layouts.app')

@yield ('variables')

@section ('script')

<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>

<script type='text/javascript'>
	var table;

	$(document).ready(function() {
		$('#table').DataTable({
			dom: 'lf<"columnVis"B>rtip',
			buttons: [
				'colvis'
			],
			rowCallback: dtRowCallback,
			'order': order,
			'pageLength': 50,
			'columnDefs': columnDefs
		});

        $("#mdActionConfirm").on('hidden.bs.modal', function(){
            $("#divError").hide();
            $("#divSuccess").hide();
            $("#divWaiting").hide();
            $("#divBase").show();

            $("#editName").text('');
            $("#btnAction").removeAttr('data-id');
            $("#btnAction").removeAttr('data-name');
            $("#btnAction").removeClass('btn-success');
            $("#btnAction").removeClass('btn-danger');
        });
	});

	function action(id, name, action) {
		$("#editName").text(name);
		$("#btnAction").attr('data-id', id);
		$("#btnAction").attr('data-name', name);
		$("#btnAction").attr('data-action', action);
		$("strong.action").text(action);

		if (action == "activate") {
            $("#btnAction").addClass('btn-success');
            $("#btnAction").html('<i class="fa fa-toggle-on"></i> Activate');
		} else if (action == "deactivate") {
            $("#btnAction").addClass('btn-danger');
            $("#btnAction").html('<i class="fa fa-trash"></i> Deactivate');
		}

		$("#mdActionConfirm").modal('show');
	}

	function cnfAction(e){
		$("#divBase").hide();
		$("#divWaiting").show();
		var id = $(e).attr('data-id');
		var name = $(e).attr('data-name');
		var action = $(e).attr('data-action');

		$.ajax({
			url: '{{$tableConfig["actionPath"]}}',
			type: 'POST',
			data: {
				'_token': '{{csrf_token()}}',
				'id': id,
				'action': action
			},
			success: function(e){
				if (e.success) {
					$("#divSuccess").show();
					$("#divSuccess p").text('The {{ $tableConfig['table'] }} "' + name + '" was successfully ' + action + "ed.");
				} else {
					$("#divError blockquote").text(e.error);
					$("#divError").show();
				}
			},
			error: function(e){
				$("#divError blockquote").text(e.status + ': ' + e.statusText);
				$("#divError").show();
			},
			complete: function(){
				$("#divWaiting").hide();
			}
		});
	}

	function actionOk(){
		location.reload();
	}
</script>

@endsection

@section ('style')

	<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'>
	<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'>

	<link rel='stylesheet' type='text/css' href='/css/tables.css' />
 
@endsection

@section ('content')
	<div class='right25'>
		<div id="mdActionConfirm" class="modal fade" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">Confirm Action</h4>
					</div>
					<div class="modal-body">
						<p>Are you sure you want to <strong class="action"></strong> the {{ $tableConfig['table'] }} "<strong id="editName"></strong>"</p>
					</div>
					<div class="modal-footer">
						<div id="divBase">
							<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-x"></i> Close</button>
							<button id="btnAction" onclick="cnfAction(this)" type="button" class="btn"></button>
						</div>
						<div id="divWaiting" style="display: none;">
							<p><i class="fa fa-spinner fa-spin"></i></p>
						</div>
						<div id="divError"  style="display: none;">
							<p class="text-danger"><i class="fa fa-exclamation-triangle"></i> Something went wrong. Please give us this error message: <blockquote>No error message provided.</blockquote></p>
						</div>
						<div id="divSuccess"  style="display: none;">
							<p class="text-success"></p>
							<button type="button" data-dismiss="modal" onclick="actionOk()" class="btn btn-primary"><i class="fa fa-check-o"></i> OK!</button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<table id='table' >
			<thead class='header'>
				<tr>
					<td></td>
					@foreach($columns as $column)
						<td>{{ $column }}</td>
					@endforeach
				</tr>
			</thead>

			<tbody>
				@foreach($contents as $content)
					<tr>
						<td class="actions"><div class="hover-div"></div></td>
						@foreach($variables as $variable)
							<td class='details-control'>{{getValue($content, $variable)}}</td>
						@endforeach
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
@endsection
<?php 
	function getValue($con, $var) {
	    try {
			if (is_array($var)) {
				switch(count($var)) {
					case 2:
						$var0 = $var[0];
						$var1 = $var[1];
						return $con->$var0->$var1;
						break;

					case 3:
						$var0 = $var[0];
						$var1 = $var[1];
						$var2 = $var[2];

						return $con->$var0->$var1->$var2;
						break;

					case 4:
						$var0 = $var[0];
						$var1 = $var[1];
						$var2 = $var[2];
						$var3 = $var[3];

						return $con->$var0->$var1->$var2->$var3;
						break;
				}
			} else {
				return $con->$var;
			}
		} catch(Exception $e) {
	        dd($con);
		}
	}
?>
