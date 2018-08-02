$(document).ready(function() {
    $('#select_payment').change(function() {
        switch($(this).val()) {
            case 'cheque':
                console.log('cheque');
                $('#bank_transfer_id').addClass('hidden');
                $('#cheque_number').removeClass('hidden');
                break;
            case 'bank_transfer':
                console.log('bank_transfer');
                $('#cheque_number').addClass('hidden');
                $('#bank_transfer_id').removeClass('hidden');
                break;
            default:
                $('#cheque_number').addClass('hidden');
                $('#bank_transfer_id').addClass('hidden');
                break;
        }
    })
});
