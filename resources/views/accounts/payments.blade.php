<script type='text/javascript' src='/js/accounts/payments.js'></script>

<div class='panel panel-default'>
    <div class='panel-heading clearfix'>
        <div class='col-md-2'>
            <button type='button' class='btn btn-primary' data-toggle="modal" data-target="#credit_card_modal" disabled><i class='fa fa-credit-card'></i>&nbsp&nbspAdd New Credit Card</button>
        </div>
        <div class='col-md-2 form-check form-check-inline'>
            <input type="checkbox" id='payOldestInvoicesFirst' name='payOldestInvoicesFirst' class='form-check-input checkbox-lg' checked />
            <label class='form-check-label' for='payOldestInvoicesFirst'>Pay Oldest Invoices First</label>
        </div>
        <div class='col-md-3'>
            <div class='input-group'>
                <span class='input-group-addon'>Payment Amount</span>
                <input type='number' min='0' step='0.01' class='form-control' name='payment_amount' placeholder='Payment Amount' />
            </div>
        </div>
        <div class='col-md-3'>
            <select id='select_payment' name='select_payment' class='form-control selectpicker' >
                {{-- TODO: show only if account has positive balance --}}
                {{-- TODO: if account_balance selected, limit the value allowed to be put against invoice --}}
                <option value='account'>Account Balance ($1000.00)</option>
                {{-- TODO: if account has credit cards on file, list each active CC --}}
                <option value='cheque'>Cheque</option>
                <option value='bank_transfer'>Bank Transfer</option>
            </select>
        </div>
        <div class='col-md-2 hidden' id='cheque_number' >
            <input type='text' name='cheque_number' class='form-control' placeholder='Cheque Number' />
        </div>
        <div class='col-md-2 hidden' id='bank_transfer_id' >
            <input type='text' name='bank_transfer_id' class='form-control' placeholder='Bank Transfer Number' />
        </div>
        <div class='col-md-12 hidden' id='select_invoices'>
            <div class='input-group'>
                <span class='input-group-addon'>Select Invoices</span>
                <select name='select_invoices' class='form-control selectpicker' multiple >
                    <option></option>
                    {{-- TODO: load all upaid invoices here --}}
                </select>
            </div>
        </div>
    </div>
    <div class='panel-body'>
        <h3>Invoice Summary</h3>
        <table style='border: 1px solid black; width:100%'>
            <thead>
                <tr>
                    <td style='border: 1px solid black'>Invoice Id</td>
                    <td style='border: 1px solid black'>Date</td>
                    <td style='border: 1px solid black'>Bill Count</td>
                    <td style='border: 1px solid black'>Balance Owing</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style='border: 1px solid black'>15</td>
                    <td style='border: 1px solid black'>02/08/2018</td>
                    <td style='border: 1px solid black'>45</td>
                    <td style='border: 1px solid black'>$289.47</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- credit card modal -->
<div id="credit_card_modal" class="modal fade" role="dialog">
	<div class="modal-dialog">
<!-- delete modal content -->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Add Credit Card</h4>
			</div>
			<div class="modal-body">
                <div class='clearfix'>
                    <form id='new_credit_card'>
                        <div class='col-md-11 bottom15'>
                            <div class='input-group'>
                                <span class='input-group-addon'>Cardholder Name</span>
                                <input type='text' name='name' class='form-control' placeholder='Cardholder Name' />
                            </div>
                        </div>
                        <div class='col-md-11 bottom15'>
                            <div class='input-group'>
                                <span class='input-group-addon'>Card Number</span>
                                <input type='number' name='cc_number' class='form-control' placeholder='Card Number' />
                            </div>
                        </div>
                        <div class='col-md-7 bottom15'>
                            <div class='input-group'>
                                <span class='input-group-addon'>Expiration Date</span>
                                <select class='form-control selectpicker' name='expiration_month' >
                                    @for($i = 1; $i < 13; $i++)
                                        <option value='{{$i}}'>{{$i}}</option>
                                    @endfor
                                </select>
                                <span class='input-group-addon'>/</span>
                                <select class='form-control selectpicker' data-live-search='true' name='expiration_year' >
                                    @for($i = 0; $i < 30; $i++)
                                        <option value='{{$i + 2018}}'>{{$i + 2018}}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class='col-md-4'>
                            <div class='input-group'>
                                <span class='input-group-addon'>CVV</span>
                                <input name='cvv' type='number' max='999' class='form-control' />
                            </div>
                        </div>
                    </form>
                </div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type='button' class="btn btn-success" onclick='saveNewCC()'>Save</button>
			</div>
		</div>
	</div>
</div>
