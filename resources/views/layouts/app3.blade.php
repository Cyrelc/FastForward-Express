@extends('layouts.html2')

@section('head')

@yield('style')

@stop

@section('body')
<div class='row'>
    <div id='reactApp' class="col-lg-12">
        @yield('content')
    </div>
</div>
@endsection

@section('footer')
@yield('script')
@parent
@endsection
