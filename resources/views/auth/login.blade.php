@extends('layouts.html')

@section('title', 'Login')

@section('head')
    <link rel='stylesheet' type='text/css' href='/css/login.css' />
@stop

@section('body')
    <div id='container'>
        <div id='loginapp'>
            <form id='loginform' method='POST' action='/login'>
                {!! csrf_field() !!}

                <table>
                    <tbody>
                        <tr>
                            <td class='ident'>
                                <span>Email</span>
                            </td>
                            <td class='input'>
                                <input type='email' name='email' value='{{ old('email') }}'>
                            </td>
                        </tr><tr>
                            <td class='ident'>
                                <span>Password</span>
                            </td>
                            <td class='input'>
                                <input type='password' name='password' id='password' size='35'>
                            </td>
                        </tr><tr>
                            <td colspan='2' class='submit'>
                                <button type='submit'>Login</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
@stop

@section('footer')

@stop
