@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg) no-repeat; position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h1 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white; text-align: left'>Contact Us</h1>
            </div>
        </div>
    </div>
    <div class='col-md-12' style='text-align: center'>
        <h1 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 15% 2px'>Get in Touch</h1>
        <h5>For all general inquiries, please use the contact form below. If you need to schedule a pickup or request a quote, please use the buttons below.</h5>
        <span>
            <a href='/requestDelivery' type='button' class='btn btn-outline-primary rounded-pill' style='margin-right: 200px'>Schedule Pickup</a>
            <a href='/requestQuote' type='button' class='btn btn-outline-primary rounded-pill'>Request a Quote</a>
        </span>
    </div>
    <div class='col-md-6 offset-md-1'>
        <h3 style='color: grey; background: linear-gradient(to right, grey, white) left bottom no-repeat; background-size: 50% 2px'>General Inquiries</h3>
        <form>
            <div class='row'>
                <div class='col-md-6'>
                    <div class="form-group">
                        <label for="comment-title">Email</label>
                        <input type="text" class="form-control" id="contact-us-email" />
                    </div>
                </div>
                <div class='col-md-6'>
                    <div class="form-group">
                        <label for="comment-title">Phone</label>
                        <input type="text" class="form-control" id="contact-us-phone" />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="comment-text">Subject</label>
                <input type="text" class="form-control" id="contact-us-subject" />
            </div>
            <div class="form-group">
                <label for="comment-text">Message</label>
                <textarea rows="10" class="form-control" id="contact-us-message"></textarea>
            </div>
        </form>
        <button type="submit" class="btn btn-primary" id="contact-us-submit">Submit</button>
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

@section('footer')
<script type="text/javascript">
    $(document).ready(function(){
        $.ajaxSetup({
           headers: {
               'X-CSRF-TOKEN': $("meta[name='csrf-token']").attr('content')
           }
        });

        $('#contact-us-submit').click(function(){
           const email = $('#contact-us-email').val();
           const phone = $('#contact-us-phone').val();
           const subject = $('#contact-us-subject').val();
           const message = $('#contact-us-message').val();

            $.ajax({
                url: '/contact',
                type: 'POST',
                data: {
                    email: email,
                    phone: phone,
                    subject: subject,
                    message: message
                },
                success: function(response) {
                    clearForm();
                    toastr.clear()
                    toastr.success('Request successfully submitted, thank you! We will respond as soon as we are able', 'Success', {
                        'progressBar' : true,
                        'positionClass': 'toast-top-full-width',
                        'showDuration': 300,
                    })
                },
                error: function(response) {
                    handleErrorResponse(response)
                }
            });
        });
    });

    function clearForm(){
        $('#contact-us-email').val('');
        $('#contact-us-phone').val('');
        $('#contact-us-subject').val('');
        $('#contact-us-message').val('');
    }
</script>
@endsection
