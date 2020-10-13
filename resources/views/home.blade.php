@extends('layouts.app3')

@section('script')
<script type='text/javascript' src='{{asset("compiled_js/App.js")}}?{{config('view.version')}}'></script>
@endsection

@section('style')
@parent
@endsection

@section('content')
<div id='reactDiv'>
</div>
@endsection
