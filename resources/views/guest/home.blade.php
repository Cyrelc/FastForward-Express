@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12' style='padding-bottom: 70px;'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg); position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h3 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white'>Welcome to the Fast Forward Express Live Beta Test! <br/>If you have been contacted to participate, click "Sign In" in the top right corner</h3>
            </div>
        </div>
    </div>
    <div class='col-md-4' style='text-align:center; padding-bottom: 50px;'>
        <img src='{{URL::to("/")}}/images/landing-request-delivery.jpg' class='img-fluid' alt='Landing-Request-Delivery'>
        <h3>Request Delivery</h3>
        <span></span>
    </div>
    <div class='col-md-4' style='text-align:center; padding-bottom: 50px;'>
        <img src='{{URL::to("/")}}/images/landing-services.jpg' class='img-fluid' alt='Landing-Services'>
        <h3>Services</h3>
        <span></span>
    </div>
    <div class='col-md-4' style='text-align:center; padding-bottom: 50px;'>
        <img src='{{URL::to("/")}}/images/landing-get-in-touch.jpg' class='img-fluid' alt='some placeholder images go here'>
        <h3>Get In Touch</h3>
        <span></span>
    </div>
    <div class='col-md-7' style='padding-right: 0px'>
        <div style='border: 4px solid black; background: url({{URL::to("/")}}/images/landing-why-FFE.jpg); height: 100%; width: 100%; position: relative'>
            <div style='background: rgba(92, 94, 212, 0.8); height: 100%'>
                <h1 class='panel-heading' style='padding-left:130px; padding-top:230px; color:white'>Why choose Fast Forward Express?</h1>
            </div>
        </div>
    </div>
    <div class='col-md-5' style='padding-left: 0px'>
        <div style='border: 4px solid black;'>
            <ul style='list-style-type:none'>
                <li style='margin: 40px 0 60px 40px'>
                    <h3><i class='fas fa-check'></i> Locally owned and operated</h3>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h3><i class='fas fa-check'></i> Proudly serving Edmonton and surrounding areas since 1993</h3>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h3><i class='fas fa-check'></i> Numerous vehicles to accommodate a wide variety of sizes of deliveries</h3>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h3><i class='fas fa-check'></i></h3>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h3><i class='fas fa-check'></i></h3>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
