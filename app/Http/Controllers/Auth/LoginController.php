<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

        $credentials = $req->only('email', 'password');
        $credentials = array_merge($credentials, ['is_enabled' => 1]);

        if (Auth::attempt($credentials)) {
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

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Your credentials are invalid, or your account has been disabled. Please contact your account administrator if you believe this to be in error.'
        ]);
    }

    public function getSanctumToken(Request $req) {
        $req->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required'
        ]);

        $user = User::where('email', $req->email)->first();

        if(!$user->hasRole('superAdmin')) {}
        else if(!$user || !$user->employee || !Hash::check($req->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect']
            ]);
        } else if(!$user->is_enabled) {
            throw ValidationException::withMessages([
                'email' => ['Your user account is disabled. Please speak with an account administrator if you believe this to be an error.']
            ]);
        }

        // Delete any old tokens for this device before issuing a new one
        $user->tokens()->where('name', $req->device_name)->delete();

        return response()->json([
            'display_name' => $user->displayName(),
            'employee_id' => $user->employee->employee_id,
            'sanctum_token' => $user->createToken($req->device_name)->plainTextToken,
            'success' => true,
        ]);
    }
}
