@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg) no-repeat; position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h1 style='padding-left:130px; padding-top:100px; color:white; text-align: left'>About Us</h1>
            </div>
        </div>
    </div>
    <div class='col-md-12'>
        <div class='row' style='padding: 20px 100px'>
            <div class='col-md-12' style='text-align: center; padding: 50px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 15% 2px;'>Our Story</h1>
                <h4>
                    Fast Forward Express is a locally-owned family-run courier service that has been operating in the Edmonton area since 1992. Our fleet includes cars, mini-vans and cargo vans, pickup trucks, and both 20ft and 24ft flat deck high boy trailers to get your deliveries to their destinations on time.
                </h4>
                <h4>
                    We want our customers to know they will receive their deliveries in the right place at the right time, which is why we've built our reputation on honesty and reliability. We provide realistic timelines for your deliveries and strive to meet every commitment.
                </h4>
            </div>
            <div class='col-md-12'>
                <h1 style='color: grey; background: linear-gradient(to right, grey, white) left bottom no-repeat; background-size: 15% 2px;'>Meet the Team</h1>
            </div>
            <div class='col-md-9 employee-profile'>
                <h3>Ritchie Nelson - President</h3>
                <h5>Ritchie founded Fast Forward Express in 1992 after finding his passion for the transportation industry starting in 1978, when he worked for a courier and express delivery company as an Owner Operator. He quickly learned about the city of Edmonton and its surrounding communities. After two years in the business, he earned his class one license and began work for various companies across Alberta, hauling trailer loads of petroleum products, fertilizers, building materials, and produce and meats, to name a few.</h5>
            </div>
            <div class='col-md-9 offset-md-3 employee-profile'>
                <h3>Vicky Nelson - Office Accounting Personnel</h3>
                <h5>Vicky has close to 30 years of experience in the industry and has been with Fast Forward Express from the beginning. Married to Ritchie, she has offered her support both in labour hours for the company and personally to support their family-run, growing business.</h5>
                <h5>Vicky works within Fast Forward's custom-made software to ensure that billing and other procedures run smoothly in the office on a daily basis.</h5>
            </div>
            <div class='col-md-9 employee-profile'>
                <h3>Curtis Nelson - Owner Operator</h3>
                <h5>Curtis, one of Ritchie's eldest sons, drives a minivan accommodating Fast Forward's customers with fast, friendly, and accurate service.</h5>
                <h5>He has vast knowledge within the company that goes back nearly {{((new DateTime('2013-06-01'))->diff(new DateTime()))->y}} years, both on the road and in the office. Curtis learned the City of Edmonton surrounding communities within months of driving for Fast Forward and is an essential part of the company's Owner Operator fleet.</h5>
            </div>
            <div class='col-md-9 offset-md-3 employee-profile'>
                <h3>Justin Nelson - Dispatch</h3>
                <h5>Justin, Ritchie's other eldest son, coordinates all aspects of the company dispatch office, receiving client orders and dispatching contracted drivers to complete them.</h5>
                <h5>He started with Fast Forward nearly {{((new DateTime('2013-06-01'))->diff(new DateTime()))->y}} years ago as the office receptionist. From there he has moved into the dispatcher position, and since then, has acquired extensive knowledge and experience in the industry.</h5>
            </div>
            <div class='col-md-9 employee-profile'>
                <h3>Riley Nelson - Office receptionist / backup dispatch</h3>
                <h5>Riley, Ritchie's youngest son, is Fast Forward's office receptionist, taking delivery requests from existing clients and answering calls from new clients, sales representatives, and numerous others.</h5>
                <h5>Riley also provides backup for dispatch where necessary. He has been with Fast Forward for nearly {{((new DateTime('2015-01-01'))->diff(new DateTime()))->y}} years.</h5>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="row">
            <div class='col-md-4' style='padding-right: 0px'>
                <div class='about-nav' style='background: #0770b1; color: white'>
                    <h4>Learn more about our</h4>
                    <h2>Services</h2>
                    <h5>Our services are personalized for your needs. Even if you need an emergency delivery, we can help.</h5>
                    <div style='text-align: center; padding-top: 25px'>
                        <a type='button' class='btn btn-outline-light' href='/services'>Read More</a>
                    </div>
                </div>
            </div>
            <div class='col-md-4' style='padding-right: 0px; padding-left: 0px'>
                <div class='about-nav' style='background: black; color: white'>
                    <h4>Access your</h4>
                    <h2>Account</h2>
                    <h5>Returning customer? Click here to access your account.</h5>
                    <div style='text-align: center; padding-top: 25px'>
                        <a type='button' class='btn btn-outline-light' href='/login'>Log in</a>
                    </div>
                </div>
            </div>
            <div class='col-md-4' style='padding-left: 0px'>
                <div class='about-nav' style='background: grey; color: white'>
                    <h4>Get a</h4>
                    <h2>Quote</h2>
                    <h5>Need a delivery quote? Fill out this online form and we'll get back to you within 24 hours.</h5>
                    <div style='text-align: center; padding-top: 25px'>
                        <a type='button' class='btn btn-outline-light' href='/requestDelivery'>Get in Touch</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
