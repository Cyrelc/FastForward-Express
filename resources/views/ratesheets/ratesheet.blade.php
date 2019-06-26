@extends('layouts.app2')

@section('script')
<script type='text/javascript' src='{{asset("compiled_js/Ratesheet.js")}}'></script>
@parent
@endsection

@section('content')
<div id='ratesheet'>
</div>
@endsection
