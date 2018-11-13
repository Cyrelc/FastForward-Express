$(document).ready(function() {
    $('#charge_type').change(function(){
        $('option', this).each(function(){$('#charge_to_' + $(this).val()).removeClass('in active')});
        $('#charge_to_' + $(this).val()).addClass('in active');
        if($(this).val() == 'account') {
            if($('#pickup_address_type').val() == 'pickup_account' && $('#delivery_address_type').val() == 'delivery_address' && $('#charge_account_id').val() == '') {
                $('#charge_account_id').val($('#pickup_account_id').val()).selectpicker('refresh').trigger('change');
                if(!$('#pickup_reference_value').hasClass('hidden') && $('#pickup_reference_value').val() != '' && $('#charge_reference_value').val() == '')
                    $('#charge_reference_value').val($('#pickup_reference_value').val());
            }
            else if($('#delivery_address_type').val() == 'delivery_account' && $('#pickup_address_type').val() == 'pickup_address' && $('#charge_account_id').val() == '') {
                $('#charge_account_id').val($('#delivery_account_id').val()).selectpicker('refresh').trigger('change');
                if(!$('#delivery_reference_value').hasClass('hidden') && $('#delivery_reference_value').val() != '' && $('#charge_reference_value').val() == '')
                    $('#charge_reference_id').val($('#delivery_reference_value').val());
            }
        }
    }).trigger('change');

    $('#prepaid_type').change(function() {
        switch($('#prepaid_type').val()) {
            case 'cash' :
                $('#prepaid_reference_value').prop('disabled', true);
                $('#prepaid_reference_value').hide();
                break;
            case 'cheque':
                $('#prepaid_reference_value').prop('disabled', false);
                $('#prepaid_reference_value').show();
                $('#prepaid_reference_value').attr('placeholder', 'Cheque Number');
                break;
            case 'bank_transfer':
                $('#prepaid_reference_value').prop('disabled', false);
                $('#prepaid_reference_value').show();
                $('#prepaid_reference_value').attr('placeholder', 'Bank Transfer ID');
                break;
            case 'visa':
            case 'mastercard':
            case 'american_express':
                $('#prepaid_reference_value').prop('disabled', false);
                $('#prepaid_reference_value').show();
                $('#prepaid_reference_value').attr('placeholder', 'Last Four Digits');
                break;
        }
    }).trigger('change');
});
