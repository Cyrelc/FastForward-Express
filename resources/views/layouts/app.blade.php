@extends('layouts.html')

@section('head')

@yield('script')

@yield('style')

@stop

@section('body')
<div class='container-fluid'>
    <div class='row'>
        <div class='col-md-12'>
            <nav class='navbar navbar-dark bg-dark'>
                <a class='navbar-brand' style='padding-left: 10px' href='/home'><h4>Fast Forward<br/>&nbsp&nbsp&nbsp&nbspExpress</h4></a>
                <a class='nav-item' href='/home'><h4>About</h4><a>
                <a class='nav-item' href='/home'><h4>Services</h4><a>
                <a class='nav-item' href='/home'><h4>Request Delivery</h4><a>
                <a class='nav-item' href='/home'><h4>Request Quote</h4><a>
                <a class='nav-item' href='/home'><h4>Contact</h4><a>
                {{-- <a class='nav-item nav-link' href='/about'><h4>About</h4></a>
                <a class='nav-item nav-link' href='/services'><h4>Services</h4></a>
                <a class='nav-item nav-link' href='/requestDelivery'><h4>Request Delivery</h4></a>
                <a class='nav-item nav-link' href='/requestQuote'><h4>Request Quote</h4></a>
                <a class='nav-item nav-link' href='/contact'><h4>Contact</h4></a> --}}
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
                    On nonsedignam ratat remquas eos sitis qui auda volum dit aditatem eos magnati officto tem res volo molore re paria consend
                    aeperum essunt eruptae velia nonse omnis restiae pore et aute
                    nam eos pre laces quis comnimint voloresto optaquae nus solupic atempostium conse vidunt mos sed qui officit endipita velit,
                    aute nis senis evenienti dolori con reici am laut utem faccus.
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
                        <textarea rows="10" class="form-control" id="comment-text"></textarea>
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
                    <p class="text-warning"><i class="fa fa-exclamation-triangle"></i> Something went wrong in submitting your feedback. Please give us this error message: <blockquote id="err-msg">No error message provided.</blockquote></p>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
@stop

@section('footer')
<script type="text/javascript">
    $(document).ready(function(){
        $.ajaxSetup({
           headers: {
               'X-CSRF-TOKEN': $("meta[name='csrf-token']").attr('content')
           }
        });

        $("#feedback-state-success").hide();
        $("#feedback-state-error").hide();

        $("#feedback-clear").click(function(){
            clearModal();
            $("#contact-us-modal").modal('hide');
        });

        $("#feedback-submit").click(function(){
           $("#feedback-submit").html('<i class="fa fa-spinner fa-spin"></i> Please Wait');

           var title = $("#comment-title").val();
           var text = $("#comment-text").val();
           var type = $("#issue-type").val();

            $.ajax({
                url: '/contactus',
                type: 'POST',
                data: {
                    title: title,
                    text: text,
                    type: type
                },
                success: function(e) {
                    if (e.success)
                        showSuccess();
                    else
                        showError(e.error);
                },

                error: function(e) {
                    showError(e.status + ': ' + e.statusText);
                }
            });
        });

        $("#contact-us-modal").on('hidden.bs.modal', function(){
            $("#feedback-submit").html('Submit <i class="fa fa-arrow-right"></i>');
            clearModal();
        });
    });

    function showSuccess(){
        $("#feedback-state-default").hide();
        $("#feedback-state-success").show();
        $("#feedback-state-error").hide();
    }

    function showError(msg){
        if (msg)
            $("#err-msg").text(msg);

        $("#feedback-state-default").hide();
        $("#feedback-state-success").hide();
        $("#feedback-state-error").show();
    }

    function clearModal(){
        $("#comment-title").val('');
        $("#comment-text").val('');
        $("#issue-type").val('bug');

        $("#feedback-state-default").show();
        $("#feedback-state-success").hide();
        $("#feedback-state-error").hide();
        $("#err-msg").text('No error message provided.');
    }
</script>
@stop
