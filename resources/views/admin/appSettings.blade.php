@extends('layouts.app2')

@section('script')
<script type='text/javascript' src='{{asset("compiled_js/AppSettings.js")}}'></script>
@parent
@endsection

@section('content')
<div id='appSettings'>
</div>
@endsection
