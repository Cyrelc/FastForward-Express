@extends ('layouts.app')

@section ('script')
<script src="//cdnjs.cloudflare.com/ajax/libs/Sortable/1.6.0/Sortable.min.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/invoices/layouts.js'></script>

@parent
@endsection

@section ('style')

@parent
@endsection

@section ('content')
<h2>Invoice Layout Designer</h2>
<form method="POST" action="/invoices/store/{{$model->account->account_id}}">
    <!--Invoice Comment-->
    <div class="col-lg-12 bottom15" id="invoice-comment">
        <label for="comment">Invoice Comment:</label>
        <textarea class="form-control" rows="5" name="comment" placeholder="This comment will appear on every invoice sent to the account">{{$model->account->invoice_comment}}</textarea>
    </div>

	<div class='col-lg-6'>
	    <ul class='list-group' id='sort_order_list'>
	    	<li class='list-group-item'>Location<input type='checkbox' id='subtotal_location' name='subtotal_location' style="float:right"/></li>
	    	<li class='list-group-item'>Date<input type='checkbox' id='subtotal_date' name='subtotal_date' style="float:right"/></li>
	    	<li class='list-group-item'>Bill Number</li>
	    	<li class='list-group-item'>Bill Text</li>
	    	<li class='list-group-item'>{{$model->account->custom_field}}<input type='checkbox' id='subtotal_custom_field' name='subtotal_custom_field' style="float:right"/></li>
	    </ul>
	</div>

    <div class='text-center'>
        <button type='submit' class='btn btn-primary'>Submit</button>
    </div>
</form>
@endsection

@section ('advFilter')

@endsection
