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
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 10% 2px'>Services</h1>
                <h3>Commolla borestrum et voluptis quodit, volor adis aut quo il idi iliqui rest, cum faccullatet pedigendi comnimp elibus, comnis este sam sit facipsanis pliquis aut harchit vel moluptatur? Aborerr oremquia coriorerion rerentis sit hit omni cus, cusaperum enit, odi conseque vit re cones ne cus quibusdam.</h3>
            </div>
            <div class='col-md-4' style='padding: 60px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Same day and overnight local service</h1>
                <h4>Rorro odis volupta eceatem et eossit dolesen ihiliquae dolupta tempor arcilis sa sit</h4>
            </div>
            <div class='col-md-4' style='padding: 60px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>After hours emergency service</h1>
                <h4>Rorro odis volupta eceatem et eossit dolesen ihiliquae dolupta tempor arcilis sa sit</h4>
            </div>
            <div class='col-md-4' style='padding: 60px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Door to door local courier service</h1>
                <h4>Rorro odis volupta eceatem et eossit dolesen ihiliquae dolupta tempor arcilis sa sit</h4>
            </div>
            <div class='col-md-6' style='padding: 60px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Personalized Service</h1>
                <h4>Rorro odis volupta eceatem et eossit dolesen ihiliquae dolupta tempor arcilis sa sit</h4>
            </div>
            <div class='col-md-6' style='padding: 60px'>
                <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 30% 2px'>Hot shot service</h1>
                <h4>Rorro odis volupta eceatem et eossit dolesen ihiliquae dolupta tempor arcilis sa sit</h4>
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
                <h3><li>And much more!</li></h3>
            </ul>
        </div>
    </div>
</div>
@endsection
