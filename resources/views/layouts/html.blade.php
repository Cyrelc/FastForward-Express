<!DOCTYPE html>
<html lang='en' style='min-height: 100%'>
    <head>
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=1024'>
        <meta name='apple-mobile-web-app-capable' content='yes'>
        <meta name='mobile-web-app-capable' content='yes'>
        <meta name='csrf-token' content='{{ csrf_token() }}'>
        <meta name='description' content='@yield('description', 'Fast Forward Express is a family-run courier serving Edmonton, St. Albert and the surrounding area since 1992, with same-day, overnight, after-hours and hot shot delivery.')'>
        <link rel='canonical' href='{{ url()->current() }}'>
        <link rel='icon' type='image/png' href='/images/fast_forward_short_logo_transparent.png'>
        <title>{{ View::hasSection('title') ? trim(View::getSection('title')) . ' | Fast Forward Express' : 'Fast Forward Express - Reliable Delivery Services in Alberta' }}</title>
        @php
            $localBusiness = [
                '@context' => 'https://schema.org',
                '@type' => 'LocalBusiness',
                'name' => 'Fast Forward Express',
                'description' => 'Locally owned, family-run courier and delivery service in the Edmonton area.',
                'url' => url('/'),
                'logo' => url('/images/fast_forward_full_logo_transparent_cropped.png'),
                'image' => url('/images/fast_forward_full_logo_transparent_cropped.png'),
                'telephone' => '+1-780-458-1074',
                'email' => 'contactus@fastforwardexpress.ca',
                'foundingDate' => '1992',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => '201 - 18 Rayborn Crescent',
                    'addressLocality' => 'St. Albert',
                    'addressRegion' => 'AB',
                    'postalCode' => 'T8N 4B1',
                    'addressCountry' => 'CA',
                ],
                'areaServed' => [
                    ['@type' => 'City', 'name' => 'Edmonton'],
                    ['@type' => 'City', 'name' => 'St. Albert'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Alberta'],
                ],
                'openingHoursSpecification' => [[
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'opens' => '08:00',
                    'closes' => '17:00',
                ]],
            ];
        @endphp
        <script type='application/ld+json'>{!! json_encode($localBusiness, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

        <script type='text/javascript' src='{{mix("/compiled_js/public.js")}}' defer></script>
        <script type='text/javascript' src="https://maps.googleapis.com/maps/api/js?key={{config('services.google.places_api_key')}}&libraries=places,geometry" defer></script>
        <script type='text/javascript' src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
        <!-- Fonts -->
        <link href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' rel='stylesheet' type='text/css' />
        <link href='https://fonts.googleapis.com/css?family=Lato:100,300,400,700' rel='stylesheet' type='text/css' />

        <!-- Styles -->
        <link rel='stylesheet' type='text/css' href='{{mix("/css/public.css")}}' />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-211586883-1"></script>
        @yield('head')
    </head>

    <body style='min-height: 100%'>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'UA-211586883-1');
            window.notyf = new Notyf({
                duration: 8000,
                position: {
                    y: 'top'
                }
            })

            function handleErrorResponse(response) {
                var errorText = '';
                var errorElements = document.querySelectorAll('.has-error');

                // Remove 'has-error' class from all elements that have it
                errorElements.forEach(function(element) {
                    element.classList.remove('has-error');
                });

                response.json().then(data => {
                    for (var key in data.errors) {
                        var inputElement = document.querySelector(`[name="${key}"]`);
                        if (inputElement) {
                            inputElement.parentElement.classList.add('has-error');
                            errorText += `<strong>${data.errors[key][0]}</strong><br/>`;
                        }
                    }

                    // Responses without field errors (e.g. rate limited) still carry a message
                    if(!errorText)
                        errorText = `<strong>${data.message || 'Something went wrong, please try again or give us a call'}</strong>`;

                    if(window.notyf) {
                        window.notyf.dismissAll()

                        window.notyf.error({
                            message: errorText,
                            dismissible: true,
                            duration: 0,
                            ripple: false,
                            icon: null
                        })
                    } else
                        console.error('Notyf was not instantiated')
                });
            }
        </script>
        @yield('body')
        @yield('footer')
    </body>
</html>
