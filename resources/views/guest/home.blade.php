@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12' style='padding-bottom: 70px;'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg) no-repeat; position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h3 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white'>We're big enough to handle it<br/>but small enough to care</h3>
            </div>
        </div>
    </div>
    <div class='col-md-4 home-tile'>
        <a href='/requestDelivery'>
            <img src='{{URL::to("/")}}/images/landing-request-delivery.jpg' class='img-fluid' alt='Landing-Request-Delivery'>
            <h3>Request Delivery</h3>
            <h5>Easily request your delivery using our online tool or by giving us a call</h5>
        </a>
    </div>
    <div class='col-md-4 home-tile'>
        <a href='/services'>
            <img src='{{URL::to("/")}}/images/landing-services.jpg' class='img-fluid' alt='Landing-Services'>
            <h3>Services</h3>
            <h5>Whether you need it delivered now, tomorrow, or next week, we've got your back!</h5>
        </a>
    </div>
    <div class='col-md-4 home-tile'>
        <a href='/contact'>
            <img src='{{URL::to("/")}}/images/landing-get-in-touch.jpg' class='img-fluid' alt='some placeholder images go here'>
            <h3>Get In Touch</h3>
            <h5>Have a question or want to connect with us? Drop us a line or call us at 780-458-1074</h5>
        </a>
    </div>
    <div class='col-md-7' style='padding-right: 0px'>
        <div style='border: 4px solid black; background: url({{URL::to("/")}}/images/landing-why-FFE.jpg) no-repeat; height: 100%; width: 100%; position: relative;'>
            <div style='background: rgba(92, 94, 212, 0.7); height: 100%'>
                <h1 class='panel-heading' style='padding-left:130px; padding-top:230px; color:white'>Why choose Fast Forward Express?</h1>
            </div>
        </div>
    </div>
    <div class='col-md-5' style='padding-left: 0px'>
        <div style='border: 4px solid black;'>
            <ul style='list-style-type:none'>
                <li style='margin: 40px 0 60px 40px'>
                    <h4><i class='fas fa-check'></i> Proud supplier of City of Edmonton's courier and freight needs for over 25 years</h4>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h4><i class='fas fa-check'></i> Locally owned and family-run</h4>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h4><i class='fas fa-check'></i> Serving the Edmonton area for nearly thirty years</h4>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h4><i class='fas fa-check'></i> Trustworthy and reliable</h4>
                </li>
                <li style='margin: 0 0 60px 40px'>
                    <h4><i class='fas fa-check'></i> Personalized service to meet your needs</h4>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
