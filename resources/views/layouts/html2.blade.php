<!DOCTYPE html>
<html lang='en' style='min-height: 100%'>
    <head>
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>
            FastForwardExpress
        </title>

        <!--Global Scripts-->
        <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js'></script>
        <script type='text/javascript' src="https://maps.googleapis.com/maps/api/js?key={{config('services.google.places_api_key')}}&libraries=places,drawing,geometry"></script>
        <script type='text/javascript' src='/js/toastr.min.js'></script>
        <script src="{{asset('/js/utils.js')}}?{{config('view.version')}}"></script>

        <!-- Fonts -->
        <link href='https://use.fontawesome.com/releases/v5.8.1/css/all.css' rel='stylesheet' type='text/css' />
        <link href='https://fonts.googleapis.com/css?family=Lato:100,300,400,700' rel='stylesheet' type='text/css' />

        <!-- Styles -->
        <link href="{{asset('css/app.css')}}?{{config('view.version')}}" rel='stylesheet' />
        <link rel='stylesheet' type='text/css' href='/css/toastr.min.css' />

        @yield('head')
    </head>

    <body style='min-height: 100%; width: 99.2vw'>
        @yield('body')
    </body>

    <footer>
        @yield('footer')
        <script type='text/javascript'>
        $(document).ready(function(){
            $.ajaxSetup({
               headers: {
                   'X-CSRF-TOKEN': $("meta[name='csrf-token']").attr('content')
               }
            });
        });
        </script>
    </footer>
</html>
