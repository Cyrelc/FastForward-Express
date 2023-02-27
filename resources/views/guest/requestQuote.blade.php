@extends('layouts.app')

@section('content')
<div class='row'>
    <div class='col-md-12'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg); position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h1 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white; text-align: left'>Request Quote</h1>
            </div>
        </div>
    </div>
    <div class='col-md-12 align-items-center' style='text-align: center'>
        <h2 style='color: grey; background: linear-gradient(to right, white, grey, white) center bottom no-repeat; background-size: 20% 2px'>Open an Account</h2>
        <h6>Have regular deliveries or bulk shipments and want to discuss a reduced rate? Reach out to us to let us know your needs and open an account, we can handle it all!</h6>
        <h6 style='padding-bottom: 25px'>Fast Forward's custom built application allows you to create and control profiles for each of your staff, allowing customizable access for requesting deliveries, billing accessibility, and much much more</h6>
        <div class='row justify-content-center'>
            <div class='col col-md-8'>
                <form>
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label for='open-account-company-name'>Company Name</label>
                                <input type='text' class='form-control' id='open-account-company-name' />
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label for='open-account-contact-name'>Contact Name</label>
                                <input type='text' class='form-control' id='open-account-contact-name' />
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label for='open-account-email'>Email</label>
                                <input type='text' class='form-control' id='open-account-email' />
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label for='open-account-phone'>Phone</label>
                                <input type='text' class='form-control' id='open-account-phone' />
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='form-group'>
                                <label for='open-account-phone'>Estimated deliveries per month</label>
                                <input type='text' class='form-control' id='deliveries-per-month' />
                            </div>
                        </div>
                        <div class='col-md-12'>
                            <div class='form-group'>
                                <label for='open-account-message'>Message</label>
                                <textarea rows='10' class='form-control' id='open-account-message' placeholder='Tell us more about your needs, your company, and what services you are looking for'></textarea>
                            </div>
                        </div>
                    </div>
                </form>
                <button type='submit' class='btn btn-primary' id='open-account-submit' style='margin-bottom: 25px'>Submit</button>
            </div>
        </div>
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

        $('#open-account-submit').click(function() {
            const companyName = $('#open-account-company-name').val();
            const contactName = $('#open-account-contact-name').val();
            const email = $('#open-account-email').val();
            const estimatedDeliveryCount = $('#deliveries-per-month').val();
            const message = $('#open-account-message').val();
            const phone = $('#open-account-phone').val();

            $.ajax({
                url: '/requestAccount',
                type: 'POST',
                data: {
                    companyName: companyName,
                    contactName: contactName,
                    email: email,
                    estimatedDeliveryCount: estimatedDeliveryCount,
                    message: message,
                    phone: phone
                },
                success: function(response) {
                    clearForm();
                    toastr.clear();
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

    function clearForm() {
        $('#open-account-email').val('');
        $('#open-account-phone').val('');
        $('#open-account-company-name').val('');
        $('#open-account-contact-name').val('');
        $('#open-account-message').val('');
        $('#deliveries-per-month').val('');
    }
</script>
@endsection
