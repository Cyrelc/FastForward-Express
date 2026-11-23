@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<form role='form' method='POST' action='{{ url('/password/reset') }}'>
    {{ csrf_field() }}
    <input type='hidden' name='token' value='{{ $token }}'>

    <div class='mb-3'>
        <label for='email' class='form-label'>E-Mail Address</label>
        <input
            type='email'
            id='email'
            name='email'
            value='{{ $email ?? old('email') }}'
            class='form-control @error('email') is-invalid @enderror'
        >
        @error('email')
            <div class='invalid-feedback'>{{ $message }}</div>
        @enderror
    </div>

    <div class='mb-3'>
        <label for='password' class='form-label'>Password</label>
        <input
            type='password'
            id='password'
            name='password'
            class='form-control @error('password') is-invalid @enderror'
            autofocus
        >
        @error('password')
            <div class='invalid-feedback'>{{ $message }}</div>
        @enderror
    </div>

    <div class='mb-4'>
        <label for='password_confirmation' class='form-label'>Confirm Password</label>
        <input
            type='password'
            id='password_confirmation'
            name='password_confirmation'
            class='form-control @error('password_confirmation') is-invalid @enderror'
        >
        @error('password_confirmation')
            <div class='invalid-feedback'>{{ $message }}</div>
        @enderror
    </div>

    <div class='alert alert-secondary small'>
        <strong>Passwords must:</strong>
        <ol class='mb-2'>
            <li>Be at least eight characters long</li>
            <li>Contain one uppercase and one lowercase character</li>
            <li>Be more than 20 characters long, or contain a number or special character</li>
        </ol>
        For strong and secure password creation tips, please see <a href='https://xkcd.com/936' target='_blank' rel='noopener'>xkcd.com/936</a>
    </div>

    <button type='submit' class='btn btn-primary'>
        <i class='fas fa-sync-alt'></i> Reset Password
    </button>
</form>
@endsection
