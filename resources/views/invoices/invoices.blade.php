@extends('layouts.app2')

@section('script')
<script type='text/javascript' src='{{asset("compiled_js/Invoices.js")}}'></script>
@parent
@endsection

@section('content')
<div id='invoices'>
</div>
@endsection
