@extends ('layouts.app')

@section ('script')
<script type='text/javascript' src='/js/invoices/invoice.js'></script>
@parent
@endsection

@section ('style')
<style type='text/css'>
table {
	page-break-inside: avoid;
}

.amount {
	float:right
}

.subtotal {
	background:#d6e0f5
}
.subtotal td.amount {
	border-top:1px solid black;
}
</style>
@parent
@endsection

@section ('content')
<div class="col-lg-12">
	<?php
		for ($i = count($model->parents) - 1; $i >= 0; $i--) {
			echo('<h4><a href="/accounts/edit/' . $model->parents[$i]->account_id . '">' . $model->parents[$i]->name . '</a></h4>');
		}
		echo("</hr>");
		echo($model->parents[0]->invoice_comment);
	?>
	<br/>
	<table style="width:90%">
		<thead>
			<tr style="border-bottom: 2px solid black">
				@foreach($model->headers as $key => $value)
					<td> {{$key}} </td>
				@endforeach
			</tr>
		</thead>
		<tbody>
			@foreach($model->table as $row)
				@if($row->is_subtotal)
					<tr class='subtotal'>
				@else
					<tr>
				@endif
				@foreach($model->headers as $key => $value)
						<td class='{{$value}}'> {{$row->$value}} </td>
				@endforeach
				</tr>
			@endforeach
			<?php
				$amounts = [["Amount:", $model->invoice->bill_cost],
							["Discount:", $model->invoice->discount],
							["Tax:", $model->invoice->tax],
							["Total:", $model->invoice->total_cost]];
				echo("<tr></tr>");
				foreach($amounts as $amount) {
					echo("<tr>");
					$i = 3;
					if (!is_null($model->parents[0]->custom_field))
						$i = 4;
					for ($i; $i > 0; $i--) {
						echo("<td></td>");
					}
					echo("<td style='text-align: right'>" . $amount[0] . "</td>");
					echo("<td style='float:right'>" . $amount[1] . "</td>");
					echo("</tr>");
				}
			?>
		</tbody>
	</table>
</div>
@endsection

@section ('advFilter')
<div class="well form-group" style='margin-top:50px'>
	<div class='text-center'>
		<a class='btn btn-info' href='/invoices/print/{{$model->invoice->invoice_id}}' target='blank'><i class='fa fa-print'>Create PDF</i></a>
	</div>
</div>
@endsection
