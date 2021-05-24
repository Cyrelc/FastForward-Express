@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12' style='padding-bottom: 70px;'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg); position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h3 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white'>Welcome to your new landing page!! <br/>(Text is all placeholder - CALL YOUR DESIGNER!!!)</h3>
            </div>
        </div>
    </div>
    <div class='col-md-4' style='text-align:center; padding-bottom: 50px;'>
        <img src='{{URL::to("/")}}/images/landing-request-delivery.jpg' class='img-fluid' alt='Landing-Request-Delivery'>
        <h3>Request Delivery</h3>
        <span>Want a delivery? Well then the least you can do is ask nicely...</span>
    </div>
    <div class='col-md-4' style='text-align:center; padding-bottom: 50px;'>
        <img src='{{URL::to("/")}}/images/landing-services.jpg' class='img-fluid' alt='Landing-Services'>
        <h3>Services</h3>
        <span>I'll give you the family rate. Double.</span>
    </div>
    <div class='col-md-4' style='text-align:center; padding-bottom: 50px;'>
        <img src='{{URL::to("/")}}/images/landing-get-in-touch.jpg' class='img-fluid' alt='some placeholder images go here'>
        <h3>Get In Touch</h3>
        <span>If you're calling about a cruise I've won, or some money I owe the IRS, I hope you know - I have no money and therefore there's nothing to scam. Please look elsewhere</span>
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
                    <h3><i class='fas fa-check'></i> Fast Forward's deliveries are punctual</h3>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h3><i class='fas fa-check'></i> Their brand new website is so functional!</h3>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h3><i class='fas fa-check'></i> Your package arrives</h3>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h3><i class='fas fa-check'></i> Between 9 and 5</h3>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h3><i class='fas fa-check'></i> A system you'll find most effectual</h3>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
