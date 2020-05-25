@extends('layouts.app2')

@section('script')
<script type='text/javascript' src='{{asset("compiled_js/Dispatch.js")}}'></script>
@parent
@endsection

@section('content')
<div id='dispatch'>
</div>
@endsection
