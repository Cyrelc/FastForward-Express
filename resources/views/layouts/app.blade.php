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
            <div id='FFELogo'>
        <nav id="menu" class="navbar navbar-inverse">
            <div class="container-fluid">
                <div class="nav navbar-nav">
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="/bills">Bills</a>
                        <ul class="dropdown-menu">
                            <li><a href="/bills/create">Create New Bill</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="/invoices">Invoices</a>
                        <ul class="dropdown-menu">
                            <li><a href="/invoices/create">Create New Invoice</a></li>
                            <li><a href="/invoices/manage_cycles">Manage Invoice Cycles</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="/customers">Customers</a>
                        <ul class="dropdown-menu">
                            <li><a href="/customers/create">Create New Customer</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="/drivers">Drivers</a>
                        <ul class="dropdown-menu">
                            <li><a href="/customers/create">Create New Driver</a></li>
                        </ul>
                    </li>
                    <li class="dropdown" disabled>
                        <a class="dropdown-toggle" data-toggle="dropdown">Dispatch</a>
                    </li>                    
                    <li class="dropdown" disabled>
                        <a class="dropdown-toggle" data-toggle="dropdown">New Delivery</a>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="/admin">Administration</a>
                        <ul class="dropdown-menu">
                            <li><a href="/customers/create">Log Out</a></li>
                        </ul>
                    </li>
                </div>
            </div>
        </nav>
        </tr>
        <div class="row">
            <div id="advFilter" class="col-lg-2">
                    @yield('advFilter')
            </div>
            <div id='content' class="col-lg-10">
                @yield('content')
            </div>
        </div>
    </tbody>
</table>
@stop

@section('footer')

@stop
