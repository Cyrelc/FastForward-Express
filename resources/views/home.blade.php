@extends('layouts.app3')

@section('script')
<script type='text/javascript' src='{{mix("compiled_js/app.js")}}'></script>
@endsection

@section('style')
@parent
@endsection

@section('content')
<div id='reactDiv'>
</div>
@endsection
