@extends('layouts.app2')

@section('script')
<script type='text/javascript' src='{{asset("compiled_js/Bill.js")}}'></script>
@parent
@endsection

@section('content')
<div id='bill'>
</div>
@endsection
