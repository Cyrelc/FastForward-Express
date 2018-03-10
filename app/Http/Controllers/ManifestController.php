<?php

namespace App\Http\Controllers;

use DB;
use PDF;
use ZipArchive;
use Response;

use App\Http\Repos;
use App\Http\Models\Manifest;
use Illuminate\Http\Request;

class ManifestController extends Controller {
    public function download($filename) {
        $path = storage_path() . '/app/public/';
        return response()->download($path . $filename)->deleteFileAfterSend(true);
    }

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
        return view('manifests.manifests');
    }

    public function buildTable(Request $req) {
        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->ListAll($req);
        return json_encode($model);
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

    public function printMass(Request $req) {
        $storagepath = storage_path() . '/app/public/';
        $foldername = 'manifests.' . time();
        mkdir($storagepath . $foldername);
        $path = $storagepath . $foldername . '/';
        $files = array();

        $zip = new ZipArchive();
        $zipfile = $storagepath . $foldername . '.zip';
        $zip->open($zipfile, ZipArchive::CREATE);

        $toBeUnlinked = array();

        foreach($req->checkboxes as $manifest_id => $value) {
            $manifestModelFactory = new Manifest\ManifestModelFactory();
            $model = $manifestModelFactory->GetById($manifest_id);
            $filename = $model->driver->contact->first_name . '.' . $model->driver->contact->last_name . '.' . 'manifest.' . $model->manifest->date_run . '.pdf';
            $is_pdf = 1;
            $pdf = PDF::loadView('manifests.manifest_pdf_layout', compact('model', 'is_pdf'));
            $pdf->save($path . $filename);
            $zip->addFile($path . $filename, $filename);
            $toBeUnlinked[$manifest_id] = $path . $filename;
        }

        $zip->close();

        foreach($toBeUnlinked as $file)
            unlink($file);
        rmdir($storagepath . $foldername);

        return $foldername . '.zip';
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
