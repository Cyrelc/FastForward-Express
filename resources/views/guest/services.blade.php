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
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Same day and overnight local service</h1>
                <h4>If you're looking for same day or overnight delivery, look no further. Our fleet will get your items delivered typically within 4-5 hours.</h4>
            </div>
            <div class='col-md-4' style='padding: 60px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>After hours emergency service</h1>
                <h4>Need something delivered outside regular business hours? We've got you covered.</h4>
            </div>
            <div class='col-md-4' style='padding: 60px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Door to door local courier service</h1>
                <h4>Whether business or residential, we can get your delivery to its destination.</h4>
            </div>
            <div class='col-md-6' style='padding: 60px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Personalized Service</h1>
                <h4>Have multiple drop off locations? Need a signature for your drop off? No problem, just let us know!</h4>
            </div>
            <div class='col-md-6' style='padding: 60px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Hot shot service</h1>
                <h4>We provide longer-distance service too. If you need something delivered to Calgary, or elsewhere in Alberta, give us a call.</h4>
            </div>
        </div>
    </div>
    <div class='col-md-12'>
    <div class='row'>
        <div class='col-md-6' style='background: url({{URL::to("/")}}/images/services.jpg) no-repeat; height: 500px; position: relative' alt='Landing Page Image'>
        </div>
        <div class='col-md-6' style='background: grey; color: white; padding: 50px; height: 500px'>
            <h1>We deliver</h1>
            <ul>
                <h3><li>Printing Products</li></h3>
                <h3><li>Oil Field Supplies</li></h3>
                <h3><li>Bids/Tenders</li></h3>
                <h3><li>Bank Deposits</li></h3>
                <h3><li>Commercial Food</li></h3>
                <h3><li>Medical Supplies & Prescriptions</li></h3>
                <h3><li>Office Supplies</li></h3>
                <h3><li>Automotive Supplies</li></h3>
                <h4><li>Dangerous Goods</li></h3>
                <h3><li>And much more!</li></h3>
            </ul>
        </div>
    </div>
</div>
@endsection
