@extends('layouts.app2')

@section('script')
<script type='text/javascript' src='{{asset("compiled_js/Charts.js")}}'></script>
@parent
@endsection

@section('content')
<div id='charts'>
</div>
@endsection
