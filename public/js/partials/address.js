var autocomplete = [];

$(document).ready(function(){
    var searchFields = $('input[id*="_location_search"]');
    $('input[id*="-zip"').each(function() {zipInput($(this).attr('id'))});
    $('input[id*="_location_search"').each(function() {
        var prefix = $(this).attr('data-div');
        autocomplete[prefix] = new google.maps.places.Autocomplete($(this)[0]);
        // TODO - re-enable. Leaving disabled temporarily to demo to Ritchie
        // autocomplete[prefix].setFields('address_components', 'geometry', 'name');
        autocomplete[prefix].addListener('place_changed', function() {fillInAddress(prefix)});
    });
});

function fillInAddress(prefix) {
    var place = autocomplete[prefix].getPlace();
    $('#' + prefix + '-name').val(place.name);
    var fields = {'street_number' : '-street', 'route' : '-street2', 'locality' : '-city', 'administrative_area_level_1' : '-province', 'country' : '-country', 'postal_code' : '-zip'};
    for(i in place.address_components) {
        var component = place.address_components[i];
        if(component.types[0] in fields)
            $('#' + prefix + fields[component.types[0]]).val(component.long_name);
    }
}

function geolocate(prefix) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var geolocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            var circle = new google.maps.Circle({
                center: geolocation,
                radius: position.coords.accuracy
            });
            autocomplete[prefix].setBounds(circle.getBounds());
        })
    }
}
