@section ('script')
<script src="//cdnjs.cloudflare.com/ajax/libs/Sortable/1.6.0/Sortable.min.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/invoices/layouts.js?{{config('view.version')}}'></script>
@parent
@endsection

@section ('style')
<style type="text/css">
#sort_order_list li label {
	float: right;
}

#sort_order_list li label input {
	margin-left: 15px;
}
</style>

@parent
@endsection

<div class='modal-dialog modal-lg'>
	<div class='modal-content'>
		<div class='modal-header'>
			<button type='button' class='close' data-dismiss='modal'>&times;</button>
			<h4 class='modal-title'>Invoice Layout Designer</h4>
		</div>
		<div class='modal-body clearfix'>
			<form id='layout-form'>
				@foreach($model->sort_options as $option)
					<input type='hidden' name='{{$option->database_field_name}}' value='' />
				@endforeach
				<!--Invoice Comment-->
				<div class="col-md-12 bottom15" id="invoice-comment">
					<label for="comment">Invoice Comment:</label><i class='fas fa-info-circle' title='This comment will appear on every invoice sent to this account. Example : "Attention: Ritchie Nelson"'></i>
					<textarea class="form-control" rows="5" name="comment" placeholder="This comment will appear on every invoice sent to the account">{{$model->account->invoice_comment}}</textarea>
				</div>
				<label>Sort Order</label>
				<div class='col-md-12'>
					<ul class='list-group' id='sort_order_list'>
					@foreach($model->sort_options as $option)
						<li class='list-group-item' name='{{$option->database_field_name}}' value='{{$option->invoice_sort_option_id}}'>{{$option->friendly_name}}
							@if($option->can_be_subtotaled)
								<label>Subtotal?<input type='checkbox' name='subtotal_{{$option->database_field_name}}' {{$option->subtotal == 1 ? 'checked' : ''}}/></label>
							@endif
						</li>
					@endforeach
					</ul>
				</div>
			</div>
			<div class='modal-footer'>
				<button type='button' class='btn btn-success' onclick='storeInvoiceLayout()'>Submit</button>
				<button type='button' class='btn btn-danger' data-dismiss='modal'>Cancel</button>
			</div>
		</form>
	</div>
</div>
