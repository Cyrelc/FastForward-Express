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
    protected $redirectTo = '/app';

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

        // Overriding the trait's login() skips its lockout, so apply it here ($maxAttempts per
        // $decayMinutes, keyed on email + IP). Throws a validation error back to the form when locked.
        if($this->hasTooManyLoginAttempts($req)) {
            $this->fireLockoutEvent($req);
            return $this->sendLockoutResponse($req);
        }

        $credentials = $req->only('email', 'password');
        $credentials = array_merge($credentials, ['is_enabled' => 1]);

        if (Auth::attempt($credentials)) {
            $this->clearLoginAttempts($req);
            // 3) Check the related account via your pivot:
            //    (adjust account() / accounts() to whatever your relation is)
            $user = Auth::user();
            if ($user->accounts()->exists() && $user->accounts()->where('active', 1)->doesntExist()) {
                Auth::logout();       // immediately log them back out
                return back()->withErrors([
                    'email' => 'Your company account is disabled. Please contact us to have it reinstated.'
                ]);
            }
            $req->session()->regenerate();
            activity('auth')->performedOn($req->user())
                ->log('Successfully authenticated user');

            return redirect()->intended('/app');
        }

        $this->incrementLoginAttempts($req);

        return back()->withErrors([
            'email' => 'Your credentials are invalid, or your account has been disabled. Please contact your account administrator if you believe this to be in error.'
        ]);
    }
}
