<?php

namespace App\Http\Controllers;

use DB;

use App\Http\Repos;
use App\Http\Models\Manifest;
use Illuminate\Http\Request;

class ManifestController extends Controller {
    public function generate() {
        // Check permissions
        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->GetGenerateModel();
        return view('manifests.manifest-generate', compact('model'));
    }

    public function getDriversToManifest(Request $req) {
        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->GetDriverListModel($req->start_date, $req->end_date);
        return view('manifests.driver-list', compact('model'));
    }
}

?>
