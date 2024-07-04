@extends('layouts.app')

@section('content')
<div class='row' id='request-delivery-form'>
    <div class='col-md-12'>
        <div style='background: url({{URL::to("/")}}/images/pexels-norma-mortenson-4391470-resized.jpg); position:relative; height: 300px;' alt='Landing Page Image'>
            <div style='background: rgba(7, 122, 177, 0.40); width: 100%; height: 100%'>
                <h1 class='panel-heading' style='padding-left:130px; padding-top:100px; color:white; text-align: left'>Request Delivery</h1>
            </div>
        </div>
    </div>
    <div class='col-md-12 request-delivery-form' style='text-align: center'>
        <h3 style='color: grey; background: linear-gradient(to right, white, grey, white) bottom no-repeat; padding-top: 20px; background-size: 50% 2px'>Request Delivery</h3>
        <form id='request-delivery-form'>
            <div class='row justify-content-center' style='padding-top:20px'>
                <div class='col-md-3'>
                    <h4 class='text-muted'>Contact Info</h4>
                </div>
                <div class='col-md-9'>
                    <div class='row bottom15'>
                        <div class='col-md-4'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Name</div></div>
                                <input type="text" class="form-control" name='contact-name' id="contact-name" />
                            </div>
                        </div>
                        <div class='col-md-4'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Phone</div></div>
                                <input type="text" class="form-control" name='phone' id="phone" />
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-4'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Email Address</div></div>
                                <input type="text" class="form-control" name='email' id="email" />
                            </div>
                        </div>
                        <div class='col-md-4'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Confirm Email Address</div></div>
                                <input type="text" class="form-control" name='email_confirmation' id="email-confirmation" />
                            </div>
                        </div>
                    </div>
                </div>
                <hr style='width:100%' />
            </div>
            <div class='row'>
                <div class='col-md-3'>
                    <h4 class='text-muted'>Pickup</h4>
                </div>
                <div class='col-md-9'>
                    <div class='row'>
                        <div class='col-md-4'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Address</div></div>
                                <input type="text" class="form-control" name='pickup-address' id="pickup-address" />
                            </div>
                        </div>
                        <div class='col-md-3'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Postal Code</div></div>
                                <input type="text" class="form-control" name='pickup-postal-code' id="pickup-postal-code" />
                            </div>
                        </div>
                        <div class='col-md-3'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Time</div></div>
                                <input type="datetime-local" class="form-control" name='pickup-time' id="pickup-time" />
                            </div>
                        </div>
                    </div>
                </div>
                <hr style='width:100%' />
            </div>
            <div class='row'>
                <div class='col-md-3'>
                    <h4 class='text-muted'>Delivery</h4>
                </div>
                <div class='col-md-9'>
                    <div class='row'>
                        <div class='col-md-4'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Address</div></div>
                                <input type="text" class="form-control" name='delivery-address' id="delivery-address" />
                            </div>
                        </div>
                        <div class='col-md-3'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Postal Code</div></div>
                                <input type="text" class="form-control" name='delivery-postal-code' id="delivery-postal-code" />
                            </div>
                        </div>
                        <div class='col-md-3'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Time (from pickup)</div></div>
                                <select name='delivery-time' id='delivery-time' name='delivery-time' class='form-control'>
                                    <option value='8' selected>8 hours or more</option>
                                    <option value='7'>7</option>
                                    <option value='6'>6</option>
                                    <option value='5'>5</option>
                                    <option value='4'>4</option>
                                    <option value='3'>3</option>
                                    <option value='2'>2</option>
                                    <option value='1'>1 hour or less</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <hr style='width:100%' />
            </div>
            <div class='row'>
                <div class='col-md-3'>
                    <h4 class='text-muted'>Package</h4>
                </div>
                <div class='col-md-9'>
                    <h6 class='text-muted'>Please enter information as accurately as possible so we can provide you with an appropriate vehicle to your request</h6>
                    <div class='row'>
                        <div class='col-md-4 bottom15'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Weight in Kg</div></div>
                                <input type="text" class="form-control" name='weight-kg' id="weight-kg" />
                            </div>
                        </div>
                        <div class='col-md-8 text-muted'>For an envelope or similar package, it is acceptable to enter 0 here</div>
                        <div class='col-md-4 bottom15'>
                            <div class='input-group mb-2'>
                                <div class='input-group-prepend'><div class='input-group-text'>Dimensions</div></div>
                                <input type="text" class="form-control" name='dimensions' id="dimensions" placeholder="Length x Weight x Height cm/m/in/ft" />
                            </div>
                        </div>
                        <div class='col-md-8 text-muted'>In the event of a significant discrepancy between the description of the package, and the package when the driver arrives, additional charges and delays may apply.</div>
                    </div>
                    <div class='col-md-12'>
                        <div class='form-group'>
                            <textarea class='form-control' rows='5' name='description' id='description' placeholder='Special delivery instructions? Is your delivery a pallet? Need special attention such as dangerous goods? Let us know here!'></textarea>
                        </div>
                    </div>
                </div>
                <hr style='width:100%' />
            </div>
        </form>
        <button type="submit" class="btn btn-primary" style='margin-bottom: 15px;' id='request-delivery-submit'>Submit Request</button>
    </div>
</div>
@endsection

@section('footer')
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        const csrfToken = document.querySelector("meta[name='csrf-token']").getAttribute('content')
        
        const now = new Date()
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
        document.getElementById('pickup-time').value = now.toISOString().slice(0, 16)

        document.getElementById('request-delivery-submit').addEventListener('click', function() {
            const data = new FormData(document.getElementById('request-delivery-form'));
            fetch('/requestDelivery', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(response => {
                clearForm()
                toastr.clear()
                toastr.success('Request successfully submitted, thank you! We will respond as soon as we are able', 'Success', {
                    'progressBar' : true,
                    'positionClass': 'toast-top-full-width',
                    'showDuration': 300,
                })
            }).catch(error => {
                handleErrorResponse(error)
            })
        })
    })

    function clearForm(){
        $('#open-account-email').val('');
        $('#open-account-phone').val('');
        $('#open-account-company-name').val('');
        $('#open-account-contact-name').val('');
        $('#open-account-message').val('');
    }
</script>
@endsection