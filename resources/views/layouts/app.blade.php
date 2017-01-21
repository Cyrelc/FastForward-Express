@extends('layouts.html')

@section('head')

@yield('script')

@yield('style')

@stop

@section('body')
<div class="row">
    <div class="col-lg-12">
        <div id='FFELogo'>
        <nav id="menu" class="navbar navbar-inverse">
            <div class="container-fluid">
                <div class="nav navbar-nav">
                    <li class="dropdown" disabled>
                        <a class="dropdown-toggle" data-toggle="dropdown" href="/bills">Bills</a>
                        <ul class="dropdown-menu">
                            <li><a href="/bills/create">Create New Bill</a></li>
                        </ul>
                    </li>
                    <li class="dropdown" disabled>
                        <a class="dropdown-toggle" data-toggle="dropdown" href="/invoices">Invoices</a>
                        <ul class="dropdown-menu">
                            <li><a href="/invoices/create">Create New Invoice</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="/accounts">Accounts</a>
                        <ul class="dropdown-menu">
                            <li><a href="/accounts/create">Create New Account</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="/drivers">Drivers</a>
                        <ul class="dropdown-menu">
                            <li><a href="/drivers/create">Create New Driver</a></li>
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

    </div>
</div>
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
