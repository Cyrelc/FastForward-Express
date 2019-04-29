<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Repos;
use App\Http\Validation;
use App\Http\Models;

class DispatchController extends Controller {
    public function view() {
        $modelFactory = new Models\Dispatch\DispatchModelFactory();
        $model = $modelFactory->GetDrivers();
        return view('dispatch.dispatch', compact('model'));
    }
}
