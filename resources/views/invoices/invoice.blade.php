@extends ('layouts.app')

@section ('script')

@parent
@endsection

@section ('style')

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
	<br>
	<table style="width:90%">
		<thead>
			<tr style="border-bottom: 2px solid black">
				<td> Date </td>
				<td>Bill Number</td>
				@if (isset($model->parents[0]->custom_field))
					<td> {{$model->parents[0]->custom_field}} </td>
				@endif
				<td> Charge Account </td>
				<td style='float:right'> Amount </td>
			</tr>
		</thead>
		<tbody>
			@foreach ($model->bills as $bill)
				<tr>
					<td> {{$bill->bill->date}} </td>
					<td> {{$bill->bill->bill_number}} </td>
					@if (isset($model->parents[0]->custom_field))
						<td>{{$bill->bill->charge_reference_value}}</td>
					@endif
					<td> {{$bill->charge_account_name}} </td>
					<td style='float:right'> {{$bill->bill->amount + $bill->bill->interliner_amount}} </td>
				</tr>
			@endforeach
			<tr style="height:45px"></tr>
			<?php
				$amounts = [["Amount:", $model->invoice->bill_cost],
							["Discount:", $model->invoice->discount],
							["Tax:", $model->invoice->tax],
							["Total:", $model->invoice->total_cost]];
				foreach($amounts as $amount) {
					echo("<tr>");
					$i = 2;
					if (!is_null($model->parents[0]->custom_field))
						$i = 3;
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
