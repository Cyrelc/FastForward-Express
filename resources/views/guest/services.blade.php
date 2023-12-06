@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg); position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h1 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white; text-align: left'>Services</h1>
            </div>
        </div>
    </div>
    <div class='col-md-12' style='text-align: center'>
        <div class='row' style='padding: 20px 100px'>
            <div class='col-md-12' style='padding: 50px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 15% 2px'>Services</h1>
                <h3>At Fast Forward Express, we provide realistic timelines for all our services, and we stick to those timelines reliably. You can count on us to provide the service you need. Our fleet of vehicles will get the job done.</h3>
            </div>
            <div class='col-md-4' style='padding: 60px'>
                <h2 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Same day and overnight local service</h2>
                <h4>If you're looking for same day or overnight delivery, look no further. Our fleet will get your items delivered typically within 4-5 hours.</h4>
            </div>
            <div class='col-md-4' style='padding: 60px'>
                <h2 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>After hours emergency service</h2>
                <h4>Need something delivered outside regular business hours? We've got you covered.</h4>
            </div>
            <div class='col-md-4' style='padding: 60px'>
                <h2 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Door to door local courier service</h2>
                <h4>Whether business or residential, we can get your delivery to its destination.</h4>
            </div>
            <div class='col-md-6' style='padding: 60px'>
                <h2 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Personalized Service</h2>
                <h4>Have multiple drop off locations? Need a signature for your drop off? No problem, just let us know!</h4>
            </div>
            <div class='col-md-6' style='padding: 60px'>
                <h2 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Hot shot service</h2>
                <h4>We can provide long-distance services as well. If you need something delivered elsewhere in Alberta or Canada give us a call!</h4>
            </div>
        </div>
    </div>
    <div class='col-md-12'>
    <div class='row'>
        <div class='col-md-6' style='background: url({{URL::to("/")}}/images/services.jpg) no-repeat; height: 500px; position: relative' alt='Landing Page Image'>
        </div>
        <div class='col-md-6' style='background: grey; color: white; padding: 40px; height: 500px'>
            <h1>We deliver</h1>
            <ul>
                <h4><li>Printing Products</li></h4>
                <h4><li>Oil Field Supplies</li></h4>
                <h4><li>Bids/Tenders</li></h4>
                <h4><li>Bank Deposits</li></h4>
                <h4><li>Commercial Food</li></h4>
                <h4><li>Medical Supplies & Prescriptions</li></h4>
                <h4><li>Office Supplies</li></h4>
                <h4><li>Automotive Supplies</li></h4>
                <h4><li>Dangerous Goods</li></h4>
                <h4><li>And much more!</li></h4>
            </ul>
        </div>
    </div>
</div>
@endsection
