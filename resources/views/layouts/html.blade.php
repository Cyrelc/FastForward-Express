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

        <script src='https://ajax.googleapis.com/ajax/libs/jquery/2.2.3/jquery.min.js'> </script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        <script src="{{URL::to('/')}}/js/utils.js"></script>

        <!-- Fonts -->
        <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css' rel='stylesheet' type='text/css' />
        <link href='https://fonts.googleapis.com/css?family=Lato:100,300,400,700' rel='stylesheet' type='text/css' />

        <!-- Styles -->
        <link href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css' rel='stylesheet' />
        <link rel='stylesheet' type='text/css' href='/css/app.css' />

        <!--Global Scripts-->

        @yield('head')
    </head>

    <body style='min-height: 100%'>
        @yield('body')
    </body>

    <footer>
        @yield('footer')
    </footer>
</html>
