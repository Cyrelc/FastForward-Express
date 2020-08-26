$(document).ready(function() {
    $('button[name="closeAmendmentModal"]').click(function() {$('#amendment_id').val('')})
    $('#bill_id').focusout(function(){
        const bill_id = $('#bill_id').val();
        const invoice_id = $('#invoice_id').val();

        $.ajax({
            'url': '/bills/getModel/' + bill_id,
            'type': 'get',
            'success': function(response){
                const bill = JSON.parse(response);
                toastr.clear();
                if(!bill) {
                    $('#submit_amendment').attr('disabled', true)
                    toastr.error('Bill requested does not exist. Please check input for errors', 'Error', {'timeOut' : 0, 'extendedTImeout' : 0})
                } else if(!bill.bill.invoice_id) {
                    $('#submit_amendment').attr('disabled', true)
                    toastr.error('Requested bill has not been invoiced yet. Unable to enter an amendment against an uninvoiced bill', 'Error', {'timeOut' : 0, 'extendedTImeout' : 0})
                } else if(bill.bill.invoice_id != invoice_id) {
                    $('#submit_amendment').attr('disabled', false)
                    toastr.warning('Bill is not part of the current invoice. This should only be performed if the bill was incorrectly assigned to another account and is being reassigned to this one.', 'Warning', {'timeOut' : 0, 'extendedTImeout' : 0})
                }
                else
                    $('#submit_amendment').attr('disabled', false)
            }
        })
    })
})

function deleteAmendment(amendmentId) {
    if(confirm('Are you sure you wish to delete the amendment? This action can not be undone.'))
        $.ajax({
            'url': '/amendments/delete/' + amendmentId,
            'type': 'GET',
            'success': function() {
                toastr.clear();
                toastr.success('Amendment successfully deleted', 'Success', {
                    'progressBar': true,
                    'showDuration': 400,
                    'onHidden': function(){location.reload()}
                })
            },
            'error': function(response){handleErrorResponse(response)}
        })
}

function submitAmendment(){
    var data = $('#amendmentForm').serialize();
    const bill_id = $('#bill_id').val();
    const storeType = $('#amendment_id').val() ? 'updated' : 'created'

    $.ajax({
        'url': '/amendments/store',
        'type': 'POST',
        'data': data,
        'success': function() {
            var storeType = $('#amendmentId').val == '' ? 'created' : 'updated';
            toastr.clear();
            toastr.success('Amendment for bill ' + bill_id + ' was successfully ' + storeType, 'Success', {
                'progressBar': true,
                'showDuration': 500,
                'onHidden': function(){location.reload()}
            })
        },
        'error': function(response){handleErrorResponse(response)}
    })
}
