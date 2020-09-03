@extends('layouts.html2')

@section('head')

@yield('style')

@stop

@section('body')
<div class='row'>
    {{-- placeholder for logo when available --}}
    <div class='col-md-3'>
    </div>
    <div class='col-md-12'>
        <nav class='navbar navbar-expand-lg navbar-dark bg-dark'>
            <a class='navbar-brand' href='#'>Fast Forward Express</a>
            <ul class='navbar-nav ml-auto'>
                @if (Auth::guest())
                    <li class='nav-item'><a href="/login">Log In <i class="fa fa-sign-in"></i></a></li>
                @else
                <li class='nav-item dropdown'>
                    <a class='nav-link dropdown-toggle' href='#' id='navbar-bills' data-toggle='dropdown'>Bills</a>
                    <div class='dropdown-menu'>
                        <a class='dropdown-item' href="/bills"><i class="fa fa-list"></i> List</a>
                        <a class='dropdown-item' href="/bills/create"><i class="fa fa-plus-square"></i> New</a>
                    </div>
                </li>
                <li class='nav-item dropdown'>
                    <a class='nav-link dropdown-toggle' href='#' id='navbar-invoices' data-toggle='dropdown'>Invoices</a>
                    <div class='dropdown-menu'>
                        <a class='dropdown-item' href="/invoices?filter[balance_owing]=0,"><i class="fa fa-list"></i> List</a>
                        <a class='dropdown-item' href="/invoices/generate"><i class='fa fa-plus-square'></i> Generate Invoices</a>
                    </div>
                </li>
                <li class='nav-item dropdown'>
                    <a class='nav-link dropdown-toggle' href='#' id='navbar-accounts' data-toggle='dropdown'>Accounts</a>
                    <div class='dropdown-menu'>
                        <a class='dropdown-item' href="/accounts"><i class="fa fa-list"></i> List Accounts</a>
                        <a class='dropdown-item' href="/accounts/create"><i class="fa fa-plus-square"></i> New Account</a>
                    </div>
                </li>
                <li class='nav-item dropdown'>
                    <a class='nav-link dropdown-toggle' href='#' id='navbar-employees' data-toggle='dropdown'>Employees</a>
                    <div class='dropdown-menu'>
                        <a class='dropdown-item' href="/employees"><i class="fa fa-list"></i> List</a>
                        <a class='dropdown-item' href="/employees/create"><i class="fa fa-plus-square"></i> New</a>
                        <a class='dropdown-item' href='/chargebacks'><i class='fa fa-tag'></i> Chargebacks</a>
                        <a class='dropdown-item' href='/manifests'><i class='fas fa-clipboard-list'></i> Manifests</a>
                        <a class='dropdown-item' href='/manifests/generate'><i class='fa fa-clipboard'></i> Generate Manifests</a>
                    </div>
                </li>
                <li class='nav-item'>
                    <a class='nav-link' href='/dispatch'>Dispatch</a>
                </li>
                <li class='nav item dropdown'>
                    <a class='nav-link dropdown-toggle' href='#' id='navbar-admin' data-toggle='dropdown'>Administration</a>
                    <div class='dropdown-menu'>
                        <a class='dropdown-item' href="/appsettings">Application Settings</a>
                        <a class='dropdown-item' href="/charts"><i class='fas fa-chart-pie'></i> Charts</a>
                        <a class='dropdown-item' href='/ratesheets'>Rate Sheets</a>
                        <a class='dropdown-item' href="/interliners"><i class="fa fa-list"></i> List Interliners</a>
                        <a class='dropdown-item' href="/interliners/create"><i class="fa fa-plus-square"></i> New Interliner</a>
                    </div>
                </li>
                <li class='nav-item'>
                    <a class='nav-link' href="/logout">Log Out</a>
                </li>
                @endif
            </ul>
        </nav>
    </div>
</div>

<div class="row">
    @if(View::hasSection('advFilter'))
        <div id="advFilter" class="col-lg-2">
                @yield('advFilter')
        </div>
        <div id='content' class="col-lg-10">
            @yield('content')
        </div>
    @else
        <div id='content' class="col-lg-12">
            @yield('content')
        </div>
    @endif
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
@endsection

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
@yield('script')
@parent
@endsection
