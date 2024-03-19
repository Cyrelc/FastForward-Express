@extends('layouts.html')

@section('head')

@yield('script')

@yield('style')

@stop

@section('body')
<div class='container-fluid'>
    <div class='row'>
        <div class='col-md-12'>
            <nav class='navbar navbar-dark bg-dark' style='padding: 0rem 1rem'>
                <a class='nav-item nav-link' style='padding: 0rem' href='/home'>
                    <img src='images/fast_forward_full_logo_transparent_cropped.png' width='260px' height='100px'>
                </a>
                <a class='nav-item nav-link' href='/about'><h4>About</h4><a>
                <a class='nav-item nav-link' href='/services'><h4>Services</h4><a>
                <a class='nav-item nav-link' href='/requestDelivery'><h4>Request Delivery</h4><a>
                <a class='nav-item nav-link' href='/requestQuote'><h4>Request Quote</h4><a>
                <a class='nav-item nav-link' href='/contact'><h4>Contact</h4><a>
                <a class='nav-item nav-link' href='/login'><h4><i class='fas fa-sign-in-alt'></i> Sign In</h4></a>
            </nav>
        </div>
    </div>
    <div class='row'>
        <div class='col-md-12' class='container-fluid'>
            @yield('content')
        </div>
    </div>
    <footer>
        <div class='row'>
            <div class='col-md-4'>
                <h2 class='footerLabel'>Contact Us</h2>
                <h4 class='footerLabel'>Address</h4>
                <h4>201 - 18 Rayborn Crescent<br>St.Albert, AB T8N 4B1</h4>
                <h4 class='footerLabel'>Phone Number</h4>
                <h4>780-458-1074</h4>
                <h4 class='footerLabel'>Email</h4>
                <h4>fastfex@telus.net</h4>
            </div>
            <div class='col-md-8'>
                <h4 style='padding-top:110px; padding-right: 50px'>
                    Here at Fast Forward Express, we've grown alongside our customers for the past {{((new DateTime('1992-06-01'))->diff(new DateTime()))->y}} years and become a vibrant, fast-paced business. We have earned our reputation by being honest and reliable. We give realistic timelines for your delivery, and we offer personalized services to best meet your needs.
                </h4>
                <h2 style='float:right; padding-right: 50px'>Fast Forward Express</h2>
            </div>
        </div>
    </footer>
</div>

{{-- <a title="Comments or Concerns?" href="#" data-toggle="modal" data-target="#contact-us-modal"><i class="fa fa-smile"></i></a> --}}

<div id="contact-us-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Feedback?</h4>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="comment-title">Title</label>
                        <input type="text" class="form-control" id="comment-title" />
                    </div>
                    <div class="form-group">
                        <label for="comment-text">Description</label>
                        <textarea rows="8" class="form-control" id="comment-text"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="comment-text">Feedback Type</label>
                        <select id="issue-type" class="form-control">
                            <option value="bug">Bug</option>
                            <option value="feature_request">Feature Request</option>
                            <option value="comment">Comment</option>
                            <option value="question">Question</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div id="feedback-state-default">
                    <button type="button" class="btn btn-default" data-dismiss="modal" id="feedback-clear"><i class="fa fa-eraser"></i> Clear</button>
                    <button type="submit" class="btn btn-primary" id="feedback-submit">Submit <i class="fa fa-arrow-right"></i></button>
                </div>

                <div id="feedback-state-success">
                    <p class="text-success"><i class="fa fa-thumbs-o-up"></i> Thank you for your feedback!</p>
                </div>

                <div id="feedback-state-error">
                    <p class="text-warning">
                        <i class="fa fa-exclamation-triangle"></i> Something went wrong in submitting your feedback. Please give us this error message: <blockquote id="err-msg">No error message provided.</blockquote>
                    </p>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
@stop

@section('footer')
@stop
