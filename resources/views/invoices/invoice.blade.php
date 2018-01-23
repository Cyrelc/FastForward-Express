@extends ('layouts.app')

@section ('script')

@parent
@endsection

@section ('style')
<style type='text/css'>
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
		for ($i = count($model->parents); $i > 0; $i--) {
			echo("<h4>" . $model->parents[$i - 1]->name . "</h4>");
		}
		echo("</hr>");
		echo($model->parents[0]->invoice_comment);
	?>
	<br>
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
				foreach($amounts as $amount) {
					echo("<tr>");
					$i = 3;
					if (!is_null($model->parents[0]->custom_field))
						$i = 4;
					for ($i; $i > '0'; $i--) {
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
