<?php

namespace App\Http\Controllers;

use App\Http\Models;
use App\Http\Repos;
use App\Http\Requests;
use App\Http\Resources\ListResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
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

    public function getAppConfiguration(Request $req) {
        $homeModelFactory = new Models\Home\HomeModelFactory();
        $model = $homeModelFactory->getAppConfiguration($req);

        return json_encode($model);
    }

    public function getDashboard(Request $req) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);
        //Get dashboard type based on assigned roles
        $homeModelFactory = new Models\Home\HomeModelFactory();
        $model = $homeModelFactory->GetAdminDashboardModel();

        return json_encode($model);
    }

    public function getLists(Request $req) {
        return new ListResource(Auth::user());
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('reactApp');
    }
}
