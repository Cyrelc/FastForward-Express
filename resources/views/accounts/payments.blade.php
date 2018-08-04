<script type='text/javascript' src='/js/accounts/payments.js'></script>
<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>

<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/css/tables.css' />

<div class='panel panel-default'>
    <div class='panel-heading clearfix'>
        <div class='col-md-12'>
            <button type='button' class='btn btn-primary' data-toggle='modal' data-target='#credit_card_modal' disabled><i class='fa fa-credit-card'></i>&nbsp&nbspAdd New Credit Card</button>
            <button type='button' class='btn btn-success' data-toggle='modal' data-target='#payment_modal'><i class='far fa-money-bill-alt'></i>&nbsp&nbspNew Payment</button>
        </div>
    </div>
    <div class='panel-body'>
        <h3>Payment History</h3>
        <table id='payments_table' width='100%'>
            <thead>
                <tr>
                    <td>Payment id</td>
                    <td>Invoice ID</td>
                    <td>Date</td>
                    <td>Amount</td>
                    <td>Payment Method</td>
                    <td>Reference Number</td>
                    <td>Notes</td>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- payment modal -->
<div id='payment_modal' class='modal fade' role='dialog'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <button type='button' class='close' data-dismiss='modal'>&times;</button>
                <h3 class='modal-title'>New Payment</h3>
            </div>
            <div class='modal-body'>
                <div class='clearfix'>
                    <form id='payment_form'>
                        <div class='col-md-8'>
                            <div class='input-group bottom15'>
                                <span class='input-group-addon'>Payment Method</span>
                                <select id='select_payment' name='select_payment' class='form-control selectpicker' >
                                    @if($model->account->account_balance > 0)
                                        <option value='account' data-amount='{{$model->account->account_balance}}'>Account Balance (${{$model->account->account_balance}})</option>
                                    @endif
                                    {{-- TODO: pull from selections table? --}}
                                    {{-- TODO: if account has credit cards on file, list each active CC --}}
                                    <option value='credit_card'>Credit Card</option>
                                    <option value='cheque'>Cheque</option>
                                    <option value='bank_transfer'>Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div class='col-md-4 bottom15 hidden' id='reference_value_div' >
                            <input type='text' id='reference_value' name='reference_value' class='form-control' />
                        </div>
                        <div class='col-md-8 bottom15'>
                            <div class='input-group'>
                                <span class='input-group-addon'>Payment Amount: $</span>
                                <input type='number' min='0' step='0.01' class='form-control' id='payment_amount' name='payment_amount' value='{{$model->account->account_balance > 0 ? $model->account->account_balance : $model->balance_owing}}' placeholder='Payment Amount' />
                            </div>
                        </div>
                        <div class='col-md-4 bottom15 form-check form-check-inline'>
                            <input type="checkbox" id='auto_pay' class='form-check-input checkbox-lg' checked />
                            <label class='form-check-label' for='auto_pay'>Auto Pay</label>
                        </div>
                        <hr>
                        <div class='col-md-12 bottom15' id='select_invoices'>
                            <h2>Outstanding Invoices</h2>
                            <table id='invoices_table' width='100%'>
                                <thead>
                                    <tr>
                                        <td>Invoice ID</td>
                                        <td>Invoice Date</td>
                                        <td>Balance Owing</td>
                                        <td>Payment Amount</td>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <div class='col-lg-12 bottom15'>
                            <div class='input-group'>
                                <span class='input-group-addon'>Remainder added to account: $</span>
                                <input type='number' readonly value='0' step='0.01' id='on_account' class='form-control' />
                            </div>
                        </div>
                        <div class='col-lg-12 bottom15'>
                            <label for='comment'>Comment: </label>
                            <textarea class='form-control' rows='3' name='comment' placeholder='Notes/Comments'></textarea>
                        </div>
                    </form>
                </div>
            </div>
			<div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type='button' class="btn btn-success" onclick='submitPayment()'>Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- credit card modal -->
<div id="credit_card_modal" class="modal fade" role="dialog">
	<div class="modal-dialog">
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
