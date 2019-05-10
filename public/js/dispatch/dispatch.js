$(document).ready(function() {
    $('*[id^="bill_list_"]').each(function(){
        Sortable.create(this, {
            group: 'connectedSortable',
            sort: false,
            animation: 150,
            handle: '.handle',
            dragoverBubble: true,
            onAdd: function(event){
                setDriver($(event.item).attr('id'), $(event.to).attr('id').replace('bill_list_', ''));
            }
        });
    });
    var position = new google.maps.LatLng(53.544389, -113.49092669999999);
    var zoom = 12;
    var map = new google.maps.Map(document.getElementById('map'), {center: position, zoom: zoom});
});

function setDriver(bill_id, driver_id) {
    // driver_id = driver_id.replace('bill_list_', '');
    //if no driver_id found, different logic
    console.log('assign bill ' + bill_id + ' to driver ' + driver_id);
}
