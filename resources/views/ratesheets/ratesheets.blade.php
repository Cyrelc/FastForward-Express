@extends('layouts.app2')

@section('script')
<script type='text/javascript' src='{{asset("compiled_js/Ratesheets.js")}}'></script>
@parent
@endsection

@section('content')
<div id='ratesheets'>
</div>
@endsection

