@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<form role='form' method='POST' action='{{ url('/login') }}'>
    {{ csrf_field() }}
    <input type='hidden' name='device_name' id='device_name'>

    <div class='mb-3'>
        <label for='email' class='form-label'>E-Mail Address</label>
        <input
            type='email'
            id='email'
            name='email'
            value='{{ old('email') }}'
            class='form-control @error('email') is-invalid @enderror'
            autofocus
        >
        @error('email')
            <div class='invalid-feedback'>{{ $message }}</div>
        @enderror
    </div>

    <div class='mb-4'>
        <label for='password' class='form-label'>Password</label>
        <input
            type='password'
            id='password'
            name='password'
            class='form-control @error('password') is-invalid @enderror'
        >
        @error('password')
            <div class='invalid-feedback'>{{ $message }}</div>
        @enderror
    </div>

    <div class='d-flex justify-content-between align-items-center'>
        <button type='submit' class='btn btn-primary'>
            <i class='fas fa-sign-in-alt'></i> Login
        </button>
        <a href='{{ url('/password/reset') }}'>Forgot your password?</a>
    </div>
</form>
@endsection

@section('scripts')
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
