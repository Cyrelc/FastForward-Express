@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg); position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h1 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white; text-align: left'>Contact Us</h1>
            </div>
        </div>
    </div>
    <div class='col-md-12' style='text-align: center'>
        <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 15% 2px'>Get in Touch</h1>
        <h5>For all general inquiries, please use the contact form below. If you need to schedule a pickup or request a quote, please use the buttons below.</h5>
        <span>
            <button type='button' class='btn btn-outline-primary rounded-pill' style='margin-right: 200px'>Schedule Pickup</button>
            <button type='button' class='btn btn-outline-primary rounded-pill'>Request a Quote</button>
        </span>
    </div>
    <div class='col-md-6 offset-md-1'>
        <h3 style='color: grey; background: linear-gradient(to right, grey, white) left bottom no-repeat; background-size: 50% 2px'>General Inquiries</h3>
    </div>
    <div class='col-md-4' style='text-align: right'>
        <h3 style='color: grey; background: linear-gradient(to right, white, grey) right bottom no-repeat; background-size: 50% 2px'>Get in Touch</h3>
        <h4><i>Phone Number</i></h4>
        <h4>780-458-1074</h4>
        <br/>
        <h4><i>Emergency/After Office Hours</i></h4>
        <h4>780-668-1074</h4>
        <br/>
        <h4><i>Email</i></h4>
        <h4>fastfex@telus.net</h4>
        <br/>
        <h4><i>Office Hours</i></h4>
        <table class='office-hours'>
            <tbody>
                <tr>
                    <td>Monday</td>
                    <td>8:00am - 5:00pm</td>
                </tr>
                <tr>
                    <td>Tuesday</td>
                    <td>8:00am - 5:00pm</td>
                </tr>
                <tr>
                    <td>Wednesday</td>
                    <td>8:00am - 5:00pm</td>
                </tr>
                <tr>
                    <td>Thursday</td>
                    <td>8:00am - 5:00pm</td>
                </tr>
                <tr>
                    <td>Friday</td>
                    <td>8:00am - 5:00pm</td>
                </tr>
                <tr>
                    <td>Saturday</td>
                    <td>Closed</td>
                </tr>
                <tr>
                    <td>Sunday</td>
                    <td>Closed</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
