<?php

namespace App\Http\Controllers;

use DB;
use PDF;

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

    public function index() {
        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->ListAll();
        return view('manifests.manifests', compact('model'));
    }

    public function view($manifest_id) {
        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->GetById($manifest_id);
        return view('manifests.manifest', compact('model'));
    }

    public function print($manifest_id) {
        //TODO check if invoice $id exists
        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->GetById($manifest_id);
        $is_pdf = 1;
        $pdf = PDF::loadView('manifests.manifest_pdf_layout', compact('model', 'is_pdf'));
        return $pdf->stream($model->driver->contact->first_name . '_' . $model->driver->contact->last_name . '.' . $model->manifest->date_run . '.pdf');
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try{
            $validationRules = [];
            $validationMessages = [];

            if(count($req->checkboxes) < 1) {
                $validationRules = array_merge($validationRules, ['drivers' => 'required']);
                $validationMessages = array_merge($validationMessages, ['drivers.required' => 'You must select at least one driver to manifest']);
            }

            $this->validate($req, $validationRules, $validationMessages);

            $manifestRepo = new Repos\ManifestRepo();

            $start_date = (new \DateTime($req->start_date))->format('Y-m-d');
            $end_date = (new \DateTime($req->end_date))->format('Y-m-d');

            $drivers = array();
            foreach($req->checkboxes as $driver)
                array_push($drivers, $driver);

            $manifestRepo->create($drivers, $start_date, $end_date);

            DB::commit();

            return;
            
        } catch(Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>
