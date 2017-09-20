@extends('layouts.html')

@section('head')

@yield('script')

@yield('style')

@stop

@section('body')
<div class="row">
    <div class="col-lg-12">
        <div id='FFELogo'>
        <nav id="menu" class="navbar navbar-inverse">
            <div class="container-fluid">
                <div class="nav navbar-nav">
                    @if (Auth::guest())
                        <li><a href="/login">Log In <i class="fa fa-sign-in"></i></a></li>
                    @else
                        <li class="dropdown" disabled>
                            <a class="dropdown-toggle" data-toggle="dropdown">Bills</a>
                            <ul class="dropdown-menu">
                                <li><a href="/bills"><i class="fa fa-list"></i> List</li>
                                <li><a href="/bills/create"><i class="fa fa-plus-square-o"></i> New</a></li>
                            </ul>
                        </li>
                        <li class="dropdown" disabled>
                            <a class="dropdown-toggle" data-toggle="dropdown" href="/invoices">Invoices</a>
                            <ul class="dropdown-menu">
                                <li><a href="/invoices"><i class="fa fa-list"></i> List</li>
                                <li><a href="/invoices/generate">Generate Invoices</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown">Accounts</a>
                            <ul class="dropdown-menu">
                                <li><a href="/accounts"><i class="fa fa-list"></i> List</a></li>
                                <li><a href="/accounts/create"><i class="fa fa-plus-square-o"></i> New</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown">Drivers</a>
                            <ul class="dropdown-menu">
                                <li><a href="/drivers"><i class="fa fa-list"></i> List</a></li>
                                <li><a href="/drivers/create"><i class="fa fa-plus-square-o"></i> New</a></li>
                            </ul>
                        </li>
                        <li class="dropdown" disabled>
                            <a class="dropdown-toggle" data-toggle="dropdown">Dispatch</a>
                        </li>
                        <li class="dropdown" disabled>
                            <a class="dropdown-toggle" data-toggle="dropdown">New Delivery</a>
                        </li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="/admin">Administration</a>
                                <ul class="dropdown-menu">
                                    <li><a href="/appsettings">Application Settings</a></li>
                                    <li><a href="/logout">Log Out</a></li>
                                </ul>
                        </li>
                    @endif

                    <li>
                        <a title="Comments or Concerns?" href="#" data-toggle="modal" data-target="#contact-us-modal"><i class="fa fa-smile-o"></i></a>
                    </li>
                </div>
            </div>
        </nav>

    </div>
</div>

<div class="row">
    <div id="advFilter" class="col-lg-2">
            @yield('advFilter')
    </div>
    <div id='content' class="col-lg-10">
        @yield('content')
    </div>
</div>

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
