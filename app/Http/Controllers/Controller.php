<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;

class Controller extends BaseController {
    use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

    public static function filter($class, $input) {
        $data = $class::all();

        foreach ($class->getFillable() as $attr) {
            if (isset($input[$attr])) {
                if (is_array($input[$attr])) {
                    $data = $data->whereIn($attr, $input[$attr]);
                } else {
                    $data = $data->where($attr, $input[$attr]);
                }
            }
        }

        return $data;
    }
}
