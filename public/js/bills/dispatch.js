$(document).ready(function() {
//driver commission auto-populate
    $("#pickup_driver_id").change(function(){
        $("#pickup_driver_commission").val($("option:selected", this).attr('data-driver-commission'));
        if (!$("#delivery_driver_id").val()) {
            $('#delivery_driver_id').val($('#pickup_driver_id').val()).trigger('change');
            if($('#time_dispatched').val() == '')
                $('#time_dispatched span').trigger('click');
        }
    });

    $("#delivery_driver_id").change(function(){
        $("#delivery_driver_commission").val($("option:selected", this).attr('data-driver-commission'));
    });

    $('#time_call_received, #time_dispatched, #time_picked_up, #time_delivered').datetimepicker({format:'MMMM Do, YYYY h:mm A'});
});
