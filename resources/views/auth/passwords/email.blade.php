@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
@if (session('status'))
    <div class='alert alert-success'>{{ session('status') }}</div>
@endif

<form role='form' method='POST' action='{{ url('/password/email') }}'>
    {{ csrf_field() }}

    <div class='mb-4'>
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

    <div class='d-flex justify-content-between align-items-center'>
        <button type='submit' class='btn btn-primary'>
            <i class='fas fa-envelope'></i> Send Password Reset Link
        </button>
        <a href='{{ url('/login') }}'>Back to login</a>
    </div>
</form>
@endsection
