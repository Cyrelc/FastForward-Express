<!DOCTYPE html>
<html lang='en' style='min-height: 100%'>
    <head>
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=1024'>
        <meta name='apple-mobile-web-app-capable' content='yes'>
        <meta name='mobile-web-app-capable' content='yes'>
        <meta name='csrf-token' content='{{ csrf_token() }}'>
        <meta name='description' content='Fast Forward Express offers reliable delivery services with realistic timelines and over 32 years of experience. We prioritize customer satisfaction.'>
        <link rel='icon' type='image/x-icon' href='/images/fast_forward_short_logo_transparent.png'>
        <title>
            Fast Forward Express - Reliable Delivery Services in Alberta
            @if(array_key_exists('title', View::getSections()))
                - @yield('title')
            @endif
        </title>

        <script type='text/javascript' src="https://maps.googleapis.com/maps/api/js?key={{config('services.google.places_api_key')}}&libraries=places,drawing,geometry" defer></script>
        <script type='text/javascript' src='{{mix("/compiled_js/public.js")}}' defer></script>

        <!-- Fonts -->
        <link href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' rel='stylesheet' type='text/css' />
        <link href='https://fonts.googleapis.com/css?family=Lato:100,300,400,700' rel='stylesheet' type='text/css' />

        <!-- Styles -->
        <link rel='stylesheet' type='text/css' href='{{mix("/css/public.css")}}' />
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-211586883-1"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'UA-211586883-1');
        </script>
        @yield('head')
    </head>

    <body style='min-height: 100%'>
        @yield('body')
    </body>

    <footer>
        @yield('footer')
    </footer>
</html>
