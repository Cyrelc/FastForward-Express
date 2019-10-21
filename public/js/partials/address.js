var autocomplete = [];

$(document).ready(function(){
    var searchFields = $('input[id*="_place_search"]');
    $('input[id*="-zip"').each(function() {zipInput($(this).attr('id'))});
    $('input[id*="_place_search"').each(function() {
        var prefix = $(this).attr('data-div');
        autocomplete[prefix] = new google.maps.places.Autocomplete($(this)[0]);
        autocomplete[prefix].setFields(['address_components', 'geometry', 'name']);
        autocomplete[prefix].addListener('place_changed', function() {updateAddress(prefix)});
        if($('#' + prefix + '_place_search').val() != '' && ($('#' + prefix + '-lat').val() == '' || $('#' + prefix + '-lng').val() == '')) {
            console.log('lat/long data not found, searching based on formatted address');
            var request = {query: $('#' + prefix + '_place_search').val(), fields:['id']};
            var service = new google.maps.places.PlacesService(document.createElement('div'));
            service.textSearch(request, function(results, status) {
                if(status === google.maps.places.PlacesServiceStatus.OK && results.length == 1) {
                    request = {placeId: results[0].place_id, fields: ['name', 'address_components', 'geometry', 'id', 'formatted_address']};
                    service.getDetails(request, function(results, status) {
                        if(status === google.maps.places.PlacesServiceStatus.OK)
                        autocomplete[prefix].set('place', results);
                        $('#' + prefix + '_place_search').val(results.formatted_address);
                        $('#' + prefix + '_place_search').parent().addClass('has-success');
                        updateAddress(prefix);
                        toastr.success('A more accurate match has automatically been found for your address input. Please verify that the data is correct, and hit "save" to not see this message again', 'Notice', {'showDuration': 1000});
                    })
                }
                else {
                    $('#' + prefix + '_place_search').parent().addClass('has-error');
                    toastr.warn('Your address is missing specific location data, but could not be automatically reconciled. Please use the provided search to select a valid address, or certain features may not be available', 'Notice');
                    drawMap(prefix);
                }
            });
        } else
            drawMap(prefix);
    });
});

function updateAddress(prefix) {
    var place = autocomplete[prefix].getPlace();
    var lat = place.geometry.location.lat();
    var lng = place.geometry.location.lng();
    //populate hidden fields
    $('#' + prefix + '-name').val(place.name);
    $('#' + prefix + '-lat').val(lat);
    $('#' + prefix + '-lng').val(lng);
    //populate address components
    var address_components = {'street_number' : '-street', 'route' : '-street2', 'locality' : '-city', 'administrative_area_level_1' : '-province', 'country' : '-country', 'postal_code' : '-zip'};
    for(i in place.address_components) {
        var component = place.address_components[i];
        if(component.types[0] in address_components)
            $('#' + prefix + address_components[component.types[0]]).val(component.long_name);
    }
    //show map
    drawMap(prefix);
}

function drawMap(prefix){
    var lat = $('#' + prefix + '-lat').val();
    var lng = $('#' + prefix + '-lng').val();
    var zoom = 15;
    var marker = true;
    if(lat == '' || lng == '') {
        lat = 53.544389;
        lng = -113.49092669999999;
        zoom = 10;
        marker = false;
    }
    var position = new google.maps.LatLng(lat, lng);
    var map = new google.maps.Map(document.getElementById(prefix + '-map'), {center: position, zoom: zoom, disableDefaultUI: true});
    if(marker)
        marker = new google.maps.Marker({map: map, position: position});
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
