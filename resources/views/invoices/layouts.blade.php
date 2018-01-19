@extends ('layouts.app')

@section ('script')
<script src="//cdnjs.cloudflare.com/ajax/libs/Sortable/1.6.0/Sortable.min.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/invoices/layouts.js'></script>
<script type='text/javascript' src='/js/toastr.min.js'></script>
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
<link rel='stylesheet' type='text/css' href='/css/toastr.min.css' />
@parent
@endsection

@section ('content')
<h2>Invoice Layout Designer</h2>
<h3>{{$model->account->name}}</h3>
<form id='layout-form'>
	<input type='hidden' name='_token' value='{{ csrf_token() }}'/>
	<input type='hidden' name='account_id' value='{{$model->account->account_id}}'/>
	@foreach($model->sort_options as $option)
		<input type='hidden' name='{{$option->database_field_name}}' value='' />
	@endforeach
	<!--Invoice Comment-->
    <div class="col-lg-12 bottom15" id="invoice-comment">
        <label for="comment">Invoice Comment:</label>
        <textarea class="form-control" rows="5" name="comment" placeholder="This comment will appear on every invoice sent to the account">{{$model->account->invoice_comment}}</textarea>
    </div>

	<div class='col-lg-6'>
		<ul class='list-group' id='sort_order_list'>
		@foreach($model->sort_options as $option)
			@if($option->can_be_subtotaled)
				<li class='list-group-item' name='{{$option->database_field_name}}' value='{{$option->invoice_sort_option_id}}'>{{$option->friendly_name}}<label>Subtotal?<input type='checkbox' name='subtotal_{{$option->database_field_name}}' {{$option->subtotal == 1 ? 'checked' : ''}}/></label></li>
			@else
				<li class='list-group-item' name='{{$option->database_field_name}}' value='{{$option->invoice_sort_option_id}}'>{{$option->friendly_name}}</li>
			@endif
		@endforeach
		</ul>
	</div>

    <div class='col-lg-12 text-center'>
        <button type='button' class='btn btn-primary' onclick='storeInvoiceLayout()'>Submit</button>
    </div>
</form>
@endsection

@section ('advFilter')

@endsection
