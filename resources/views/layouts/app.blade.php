@extends('layouts.html')

@section('head')

<!-- Fonts -->
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css' rel='stylesheet' type='text/css' />
<link href='https://fonts.googleapis.com/css?family=Lato:100,300,400,700' rel='stylesheet' type='text/css' />

<!-- Styles -->
<link href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css' rel='stylesheet' />
<link rel='stylesheet' type='text/css' href='/css/app.css' />

@yield('script')

    @yield('style')
@stop

@section('body')
<table id='mainWindow'>
    <tbody>
        <tr>
            <td id='menuBar' colspan='2'>
                <div id='menuBar'>
                    <div id='FFELogo'>
                        <button id='logout' onclick='location.href="/logout"'>Log out</button>
                        <ul id='menu'>
                            <li>
                                <a href='/bills'>Bills</a>
                            </li>
                            <li>
                                <a href='/invoices'>Invoices</a>
                            </li>
                            <li>
                                <a href='/customers'>Customers</a>
                            </li>
                            <li>
                                <a href='/drivers'>Drivers</a>
                            </li>
                            <li>
                                <a href='/dispatch'>Dispatch</a>
                            </li>
                            <li>
                                <a href='/new-delivery'>New Delivery</a>
                            </li>
                            <li>
                                <a href='/administration'>Administration</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td id='navAndFilter'>
                <div id='navBar'>
                    @yield('navBar')
                </div>
                <div id='advFilter'>
                    @yield('advFilter')
                </div>
            </td>
            <td id='detailsAndContent'>
                <div id='content'>
                    @yield('content')
                </div>
            </td>
        </tr>
    </tbody>
</table>
@stop

@section('footer')

@stop
