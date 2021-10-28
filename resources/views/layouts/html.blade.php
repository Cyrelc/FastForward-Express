<!DOCTYPE html>
<html lang='en' style='min-height: 100%'>
    <head>
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>
            FastForwardExpress
            @if(array_key_exists('title', View::getSections()))
                - @yield('title')
            @endif
        </title>

        <script src='https://ajax.googleapis.com/ajax/libs/jquery/2.2.3/jquery.min.js'></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js'></script>
        <script type='text/javascript' src="https://maps.googleapis.com/maps/api/js?key={{config('services.google.places_api_key')}}&libraries=places,drawing,geometry"></script>
        <script type='text/javascript' src='/js/toastr.min.js'></script>
        <script src="{{URL::to('/')}}/js/utils.js"></script>

        <!-- Fonts -->
        <link href='https://use.fontawesome.com/releases/v5.8.1/css/all.css' rel='stylesheet' type='text/css' />
        <link href='https://fonts.googleapis.com/css?family=Lato:100,300,400,700' rel='stylesheet' type='text/css' />

        <!-- Styles -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
        <link rel='stylesheet' type='text/css' href='/css/app2.css' />
        <link rel='stylesheet' type='text/css' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css' />
        <link rel='stylesheet' type='text/css' href='/css/toastr.min.css' />

        <!--Global Scripts-->
        <script type="text/javascript" src="{{URL::to('/')}}/js/moment.min.js"></script>
        <script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-datetimepicker.min.js"></script>

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
