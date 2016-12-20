<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class DriverController extends Controller {
    public function __construct() {
        $this->middleware('auth');

        //API STUFF
        $this->sortBy = 'name';
        $this->maxCount = env('DEFAULT_DRIVER_COUNT', $this->maxCount);
        $this->itemAge = env('DEFAULT_DRIVER_AGE', '1 month');
        $this->class = new \App\Driver;
    }

    public function create(){
        return view('drivers.create_driver');
    }

    protected function genFilterData($input) {
        return null;
    }

    public function index() {
        return view('drivers.drivers');
    }
}
