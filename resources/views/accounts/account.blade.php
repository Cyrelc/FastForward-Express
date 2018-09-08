@extends ('layouts.app')

@section ('script')
<script type="text/javascript" src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script type="text/javascript" src="https://nosir.github.io/cleave.js/js/lib.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/validation.js'></script>
<script type="text/javascript" src="{{URL::to('/')}}/js/bootstrap-combobox.js"></script>
<script type='text/javascript' src='{{URL::to('/')}}/js/accounts/account.js?{{config('view.version')}}'></script>
@parent
@endsection

@section ('style')
<link rel="stylesheet" type="text/css" href="{{URL::to('/')}}/css/bootstrap-combobox.css" />
<link rel='stylesheet' type='text/css' href='/css/accounts/account.css' />
@parent
@endsection

@section ('content')
    @if (isset($model->account->account_id))
        <h2>Manage Account</h2>
    @else
        <h2>New Account</h2>
    @endif
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <ul class='nav nav-tabs'>
        <li class='active'><a data-toggle='tab' href='#basic'>Basic Info</a></li>
        {{-- <li><a data-toggle='tab' href='#admin'>Admin</a></li> --}}
        <li><a data-toggle='tab' href='#advanced'>Advanced</a></li>
        @if(isset($model->account->account_id))
            <li><a data-toggle='tab' href='#commissions'>Commissions</a></li>
            <li><a data-toggle='tab' href='#payments'>Payments</a></li>
            <li><a disabled data-toggle='tab' href='#invoice_layouts'>Invoice Layout</a></li>
        @endif
    </ul>
    <div class='tab-content'>
        <div id='basic' class="tab-pane fade in active well">
            @include('accounts.basic')
        </div>
        {{-- <div id='admin' class='tab-pane fade well'>
            @include('accounts.admin')
        </div> --}}
        <div id='advanced' class='tab-pane fade well'>
            @include('accounts.advanced')
        </div>
        <div id='commissions' class='tab-pane fade well'>
            @include('accounts.commissions')
        </div>
        @if(isset($model->account->account_id))
            <div id='payments' class='tab-pane fade well'>
                @include('accounts.payments')
            </div>
            <div id='invoice_layout' class='tab-pane fade well'>
            </div>
        @endif
    </div>
    <div class='col-lg-4 text-center'>@if(isset($model->prev_id))<a class='btn btn-info' href='/accounts/edit/{{$model->prev_id}}'>Previous Account</a>@endif</div>
    <div class='col-lg-4 text-center'><button type='button' class='btn btn-primary' onclick='storeAccount()'>Submit</button></div>
    <div class='col-lg-4 text-center'>@if(isset($model->next_id))<a class='btn btn-info' href='/accounts/edit/{{$model->next_id}}'>Next Account</a>@endif</div>
</div>
@endsection

@section ('advFilter')
<div class="well form-group">
    <div class='clearfix text-center'>
        @if(isset($model->account->account_id))
            <h4>Navigation<h4>
            <a class='btn btn-info bottom15 col-md-10' href='/invoices/layouts/{{$model->account->account_id}}'>Go To Invoice Layout</a>
            <a disabled class='btn btn-basic bottom15 col-md-10' href='' >View Bills</a>
            <a disabled class='btn btn-basic bottom15 col-md-10' href='' >View Invoices</a>
        @endif
    </div>
</div>
@endsection
