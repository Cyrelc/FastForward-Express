<!DOCTYPE html>
<html lang='en' style='min-height: 100%'>
    <head>
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>

        <title>
            FastForwardExpress
            @if(array_key_exists('title', View::getSections()))
                - @yield('title')
            @endif
        </title>

        <script src='https://ajax.googleapis.com/ajax/libs/jquery/2.2.3/jquery.min.js'> </script>
        <script src="{{URL::to('/')}}/js/utils.js"></script>
        @yield('head')
    </head>

    <body style='min-height: 100%'>
        @yield('body')
    </body>

    <footer>
        @yield('footer')
    </footer>
</html>
