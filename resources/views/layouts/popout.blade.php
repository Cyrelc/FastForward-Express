@extends('layouts.html')

@section('head')
    <link rel='stylesheet' type='text/css' href='/css/popout.css' />
@stop

@section('body')
    <div id='container'>
        <div id='content'>
            @yield('content')
        </div>
        <div id='buttons'>
            @yield('buttons')
        </div>
    </div>
@stop
