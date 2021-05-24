@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg); position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h1 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white; text-align: left'>About Us</h1>
            </div>
        </div>
    </div>
    <div class='col-md-12' style='text-align: center'>
        <div class='row' style='padding: 20px 100px'>
            <div class='col-md-12' style='text-align: center; padding: 50px'>
                <h2 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 2px;'>Our Story</h2>
                <h3>Commolla borestrum et voluptis quodit, volor adis aut quo il idi iliqui rest, cum faccullatet pedigendi comnimp elibus, comnis este sam sit facipsanis pliquis aut harchit vel moluptatur? Aborerr oremquia coriorerion rerentis sit hit omni cus, cusaperum enit, odi conseque vit re cones ne cus quibusdam.</h3>
            </div>
            <div class='col-md-12' style='text-align: left;'>
                <h2 style='color: grey; background: linear-gradient(to right, grey, white) left bottom no-repeat; background-size: 15% 2px;'>Meet the Team</h2>
            </div>
            <div class='col-md-2'>
                <img src='https://via.placeholder.com/250x300' class='img-fluid' alt='About-Us-1'>
            </div>
            <div class='col-md-10'>
                <h3 style='padding-top: 30px'>Pa aspid quos rehendam erro corum velest, te nulpa cusandae comniet faccuptatur? Quiae sinihiciis alit res ex eum utemoditatem quidem dolupta etur? Quia corum sequi rendest rerib.</h3>
            </div>
            <div class='col-md-10'>
                <h3 style='padding-top: 30px'>Pa aspid quos rehendam erro corum velest, te nulpa cusandae comniet faccuptatur? Quiae sinihiciis alit res ex eum utemoditatem quidem dolupta etur? Quia corum sequi rendest rerib.</h3>
            </div>
            <div class='col-md-2'>
                <img src='https://via.placeholder.com/250x300' class='img-fluid' alt='About-Us-2'>
            </div>
        <div class='col-md-2'>
                <img src='https://via.placeholder.com/250x300' class='img-fluid' alt='About-Us-3'>
            </div>
            <div class='col-md-10'>
                <h3 style='padding-top: 30px'>Pa aspid quos rehendam erro corum velest, te nulpa cusandae comniet faccuptatur? Quiae sinihiciis alit res ex eum utemoditatem quidem dolupta etur? Quia corum sequi rendest rerib.</h3>
            </div>
        </div>
    </div>
    <div class='col-md-4' style='background: #0770b1; color: white'>
        <h2 style='font-weight: bold'>Learn more about our</h2>
        <h1 style='font-weight: bold'>Services</h1>
    </div>
    <div class='col-md-4' style='background: black; color: white'>
        <h2 style='font-weight: bold'>Access your</h2>
        <h1 style='font-weight: bold'>Account</h1>
    </div>
    <div class='col-md-4' style='background: grey; color: white'>
        <h2 style='font-weight: bold'>Get a</h2>
        <h1 style='font-weight: bold'>Quote</h1>
    </div>
</div>
@endsection
