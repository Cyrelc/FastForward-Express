@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-center">
    <div class="col-md-6">
        <div class="card" style="margin: 20px">
            <div class="card-header"><h4 class='card-title'>Login</h4></div>
            <div class="card-body">
                <form class="form-horizontal" role="form" method="POST" action="{{ url('/login') }}">
                    {{ csrf_field() }}
                    <input type='hidden' name='device_name' id='device_name'>

                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                        <div class="col-md-12">
                            <div class='input-group'>
                                <div class='input-group-prepend'>
                                    <span class='input-group-text'>E-Mail Address</span>
                                </div>
                                <input type="email" class="form-control" name="email" value="{{ old('email') }}">

                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                        <div class="col-md-12">
                            <div class='input-group'>
                                <div class='input-group-prepend'>
                                    <span class='input-group-text'>Password</span>
                                </div>
                                <input type="password" class="form-control" name="password">

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-6 col-md-offset-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="remember"> Remember Me
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-6 col-md-offset-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-btn fa-sign-in"></i> Login
                            </button>

                            <a class="btn btn-link" href="{{ url('/password/reset') }}">Forgot Your Password?</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Get the computer name
        var deviceName = navigator.platform || 'Unknown Device';

        // Set the value of the hidden field
        var deviceNameInput = document.getElementById('device_name');
        deviceNameInput.value = deviceName;
    });
</script>
@endsection
