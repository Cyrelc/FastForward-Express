@extends ('layouts.app')

@section ('script')

@parent
@endsection

@section ('style')

@parent
@endsection

@section ('content')
	<?php
		for ($i = count($model->parents); $i > 0; $i--) {
			echo("<h4>" . $model->parents[$i - 1]->name . "</h4>");
		}
		echo("</hr>");
		echo($model->parents[0]->invoice_comment);
	?>
	<br>
	<br>
	<table style="width:100%">
		<thead>
			<tr>
				<td>Bill Id</td>
				<?php
					if (!is_null($model->parents[0]->custom_field))
						echo("<td>" . $model->parents[0]->custom_field . "</td>");
				?>
				<td> Date </td>
				<td> Pickup </td>
				<td> Delivery </td>
				<td> Amount </td>
			</tr>
		</thead>
		<tbody>
			@foreach ($model->bills as $bill)
				<tr>
					<td> {{$bill->bill->bill_id}} </td>
					<td> {{$bill->bill->date}} </td>
					<td> {{$bill->pickup_address->name}} </td>
					<td> {{$bill->delivery_address->name}} </td>
					<td> {{$bill->bill->amount}} </td>
				</tr>
			@endforeach
			<tr></tr>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td>Charge</td>
				<td>{{$model->amount}}</td>
			</tr>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td>Tax</td>
				<td>{{$model->tax}}</td>
			</tr>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td>Total</td>
				<td>{{$model->total}}</td>
			</tr>
		</tbody>
	</table>
@endsection
