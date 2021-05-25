<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Sets max login attempts before lockout, and lockout duration
     */
    protected $maxAttempts = 10;
    protected $decayMinutes = 30;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    public function login(Request $req) {
        /**
         * Handle an authentication attempt
         *
         * @param \Illuminate\Http\Request
         * @return \Illuminate\Http\Response
         */

        $credentials = $req->only('email', 'password');
        $credentials = array_merge($credentials, ['is_enabled' => 1]);

        if (Auth::attempt($credentials)) {
            $req->session()->regenerate();

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Your credentials are invalid, or your account has been disabled. Please contact your account administrator if you believe this to be in error.'
        ]);
    }
}
