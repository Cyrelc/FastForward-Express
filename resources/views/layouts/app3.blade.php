@extends('layouts.html2')

@section('head')

@yield('style')

@stop

@section('body')
<div class='row' style='width: 99.1vw'>
    <div id='reactApp' class="col-lg-12" style="padding: 0">
        @yield('content')
    </div>
</div>
@endsection

@section('footer')
@yield('script')
@parent
@endsection
