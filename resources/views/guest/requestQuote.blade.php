@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg); position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h1 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white; text-align: left'>Request Quote</h1>
            </div>
        </div>
    </div>
    <div class='col-md-12' style='text-align: center'>
        <h2>Coming soon!</h2>
    </div>
</div>
@endsection
