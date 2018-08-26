$(document).ready(function() {
//driver commission auto-populate
    $("#pickup_driver_id").change(function(){
        $("#pickup_driver_commission").val($("option:selected", this).attr('data-driver-commission')*100);
        if (!$("#delivery_driver_id").val()) {
            $('#delivery_driver_id').val($('#pickup_driver_id').val()).trigger('change');
        }
    });

    $("#delivery_driver_id").change(function(){
        $("#delivery_driver_commission").val($("option:selected", this).attr('data-driver-commission')*100);
    });
});
