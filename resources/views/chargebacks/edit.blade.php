<form id='chargeback_edit'>
    @foreach($model->employees as $employee)
    <h3>{{$employee->contact->first_name}} {{$employee->contact->last_name}}</h3>
        <table class='table table-bordered table-striped'>
            <thead>
                <tr>
                    <td>Name</td>
                    <td>Start Date</td>
                    <td>Amount</td>
                    <td>GL Code</td>
                    <td>Count Remaining</td>
                    <td>Continuous</td>
                    <td></td>
                </tr>
            </thead>
            <tbody>
                @foreach($employee->chargebacks as $chargeback)
                    <tr id='chargeback_{{$chargeback->chargeback_id}}'>
                        <td><input class='form-control' type='text' name='name' value='{{$chargeback->name}}' /></td>
                        <td><input class='form-control' type='date' name='start_date' value='{{$chargeback->start_date}}' /></td>
                        <td>
                            <div class='input-group'>
                                <span class='input-group-addon'>$</span>
                                <input class='form-control' type='number' name='amount' step='0.01' value='{{$chargeback->amount}}' />
                            </div>
                        </td>
                        <td><input class='form-control' type='text' name='gl_code' value='{{$chargeback->gl_code}}' /></td>
                        <td><input class='form-control' type='number' name='count_remaining' value='{{$chargeback->count_remaining}}' /></td>
                        <td><input class='form-control' type='checkbox' name='continuous' {{$chargeback->continuous == 1 ? 'checked' : ''}} /></td>
                        <td>
                            <button class='btn btn-success' type='button' onclick='updateChargeback({{$chargeback->chargeback_id}})'>Update</button>
                            <button class='btn btn-danger' type='button' data-toggle="modal" data-target="#deactivate_modal" onclick='setDeactivateId({{$chargeback->chargeback_id}})'>Deactivate</button>
                        </td>
                    </tr>
                @endforeach
            <tbody>
        </table>
    @endforeach
</form>

<!-- deactivate modal -->
<div id="deactivate_modal" class="modal fade" role="dialog">
	<div class="modal-dialog">
<!-- deactivate modal content -->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Confirm Deactivation of Chargeback</h4>
			</div>
			<div class="modal-body">
				<p id="delete_message">Please confirm deactivation of chargeback. If you wish to reapply the chargeback, you will have to create a new one.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<a id="deactivate_button" type="button" class="btn btn-danger">Deactivate</a>
			</div>
		</div>
	</div>
</div>
