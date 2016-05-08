<!DOCTYPE html>
<html lang='en' style='min-height: 100%'>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>

    <title>FastForwardExpress</title>

    <!-- Fonts -->
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css' rel='stylesheet' type='text/css' />
    <link href='https://fonts.googleapis.com/css?family=Lato:100,300,400,700' rel='stylesheet' type='text/css' />

    <!-- Styles -->
    <link href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css' rel='stylesheet' />
    <link rel='stylesheet' type='text/css' href='/css/app.css' />

    @yield('script')

    @yield('style')

</head>

<body style='min-height: 100%'>
    <table id='mainWindow'>
        <tbody>
            <tr>
                <td id='menuBar' colspan='2'>
                    <div id='menuBar'>
                        <div id='FFELogo'>
                            <button id='logout'>Log out</button>
                            <ul id='menu'>
                                <li><a href='\bills'>Bills</a></li>
                                <li><a href='\'>Invoices</a></li>
                                <li><a href='\customers'>Customers</a></li>
                                <li><a href='\drivers'>Drivers</a></li>
                                <li><a href='\'>Dispatch</a></li>
                                <li><a href='\'>New Delivery</a></li>
                                <li><a href='\'>Administration</a></li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td id='navAndFilter'>
                    <div id='navBar'>
                        <ul>

                        </ul>
                    </div>
                    <div id='advFilter'>
                        @yield('advFilter')
                        advFilter goes here
                    </div>
                </td>
                <td id='detailsAndContent'>
                    <div id='details'>
                        @yield('details')
                        details go here
                    </div>
                    <div id='content'>
                        @yield('content')
                        content goes here
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
