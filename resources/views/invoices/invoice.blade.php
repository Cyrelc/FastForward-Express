@extends ('layouts.app')

@section ('script')
<script type='text/javascript' src='/js/invoices/invoice.js?{{config('view.version')}}'></script>
@parent
@endsection

@section ('style')
@parent
@endsection

@section ('content')
<div class="col-lg-11">
	<div>
		@include('invoices.invoice_table')
	</div>
</div>
@endsection

@section ('advFilter')
<div class="well form-group" style='margin-top:50px'>
	<div class='text-center'>
		<div class='bottom15'>
			<a class='btn btn-info bottom15' href='/invoices/print/{{$model->invoice->invoice_id}}' target='blank'><i class='fa fa-print'> Create PDF</i></a>
		</div>
		@if(isset($model->amendments))
			<div class='bottom15'>
				<a class='btn btn-info bottom15' href='/invoices/print/{{$model->invoice->invoice_id}}?amendments_only' target='blank'><i class='fa fa-print'> Create PDF (Amendments Only)</i></a>
			</div>
		@endif
		{{-- todo - needs is_admin checks --}}
		<button type='button' class='btn btn-warning' data-toggle='modal' data-target='#amendmentModal'><i class='fas fa-eraser'> Create Amendment</i></a>
	</div>
</div>
{{-- todo - should only include if is_admin --}}
<div id='amendmentModal' class='modal fade' role='dialog'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>
				<button type='button' name='closeAmendmentModal' class='close' data-dismiss='modal'>&times;</button>
				<h3 class='modal-title'>Create Amendment</h3>
			</div>
			<div class='modal-body'>
				<div class='clearfix'>
					<h4 style='color:red'>This feature is in BETA. Please review the invoice carefully BEFORE AND AFTER to ensure the values are correct.</h4>
					<form id='amendmentForm'>
						<input type='hidden' id='invoice_id' name='invoice_id' value='{{$model->invoice->invoice_id}}' />
						<input type='hidden' id='amendment_id' name='amendment_id' value='' />
						<div class='input-group bottom15'>
							<span class='input-group-addon'>Bill ID:</span>
							<input type='number' id='bill_id' min='1' name='bill_id' class='form-control' />
						</div>
						<div class='bottom15'>
							<label for='comment'>Comment: </label>
							<textarea class='form-control' rows='3' name='description' placeholder='Notes/Comments'></textarea>
						</div>
						<div class='input-group bottom15'>
							<span class='input-group-addon'>Adjust Invoice By Amount:</span>
							<input class='form-control' type='number' id='amount' name='amount' placeholder='Amount' step='0.01' />
							<span class='input-group-addon'><i class='fa fa-question' title='To credit an account, enter a negative number'></i></span>
						</div>
					</form>
				</div>
			</div>
			<div class='modal-footer'>
				<button type='button' class='btn btn-default' data-dismiss='modal' name='closeAmendmentModal'>Cancel</button>
				<button type='button' class='btn btn-success' onClick='submitAmendment()' id='submit_amendment'>Submit</button>
			</div>
		</div>
	</div>
</div>
@endsection
