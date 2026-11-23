<!DOCTYPE html>
<html lang='en'>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <meta name='robots' content='noindex'>
        <link rel='icon' type='image/png' href='/images/fast_forward_short_logo_transparent.png'>
        <title>@yield('title') - Fast Forward Express</title>

        <!-- Fonts -->
        <link href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' rel='stylesheet' type='text/css' />

        <!-- Styles -->
        <link href='{{mix("/css/app.css")}}' rel='stylesheet' />
    </head>

    <body class='bg-light'>
        <main class='container' style='max-width: 480px; padding-top: 10vh'>
            <div class='text-center mb-4'>
                <img src='/images/fast_forward_full_logo_transparent_cropped.png' alt='Fast Forward Express' width='260'>
            </div>
            <div class='card shadow-sm'>
                <div class='card-body p-4'>
                    <h1 class='h4 mb-4'>@yield('title')</h1>
                    @yield('content')
                </div>
            </div>
        </main>

        @yield('scripts')
    </body>
</html>
