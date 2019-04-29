$(document).ready(function() {
    // console.log($('ul[id^="bill_list"]'));
    $('ul[id^="bill_list"]').sortable({
        connectWith: '.connectedSortable',
        handle: '.handle',
    })
    $('ul[id^="bill_list"] li').droppable({
        drop: function() {
            setDriver($(this).attr('id'), $(this).closest('ul').attr('id'));
        }
    })
});

function setDriver(bill_id, driver_id) {
    driver_id = driver_id.replace('bill_list_', '');
    //if no driver_id found, different logic
    console.log('assign bill #' + bill_id + ' to driver ' + driver_id);
}
