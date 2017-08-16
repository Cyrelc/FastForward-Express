<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function contactUs(Request $req){
        try {
            \Mail::send('emails.feedback', array('title' => $req->input('title'), 'text' => $req->input('text'), 'type' => $req->input('type')), function($m) use($req) {
                $m->from('fastforwardexpressfeedback@gmail.com', 'FastForward Express Feedback');
                $m->to(env('MAIL_USERNAME'))->subject('Feedback from FFE: ' . $req->input('type'));
            });

            return response()->json([
                'success' => true
            ]);
        } catch(Exception $e){
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function AppSettings() {
        return view('admin.adminSettings');
    }

}
