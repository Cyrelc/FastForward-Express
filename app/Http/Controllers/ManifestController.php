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
    public function buildTable(Request $req) {
        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $user = $req->user();
        if($user->cannot('viewAny', Manifest::class))
            abort(403);
        elseif($user->can('manifests.view.*'))
            $model = $manifestModelFactory->ListAll($req);
        elseif($user->employee && $user->employee->is_driver)
            $model = $manifestModelFactory->ListAll($req, $user->employee->employee_id);
        else
            abort(403);

        return json_encode($model);
    }

    public function delete(Request $req, $manifest_id) {
        if($req->user()->cannot('delete', Manifest::class))
            abort(403);

        DB::beginTransaction();

        $manifestRepo = new Repos\ManifestRepo();
        $manifestRepo->delete($manifest_id);

        DB::commit();
    }

    public function getDriversToManifest(Request $req) {
        if($req->user()->cannot('create', Manifest::class))
            abort(403);

        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $drivers = $manifestModelFactory->GetDriverListModel($req->start_date, $req->end_date);
        return json_encode($drivers);
    }

    public function getModel(Request $req, $manifest_id) {
        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->GetById($manifest_id);

        if($req->user()->cannot('view', $model->manifest))
            abort(403);

        return json_encode($model);
    }

    public function print(Request $req, $manifest_id) {
        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->GetById($manifest_id);

        if($req->user()->cannot('view', $model->manifest))
            abort(403);

        $without_bills = isset($req->without_bills);
        $pdf = PDF::loadView('manifests.manifest_pdf_layout', compact('model', 'without_bills'));
        return $pdf->stream($model->employee->contact->first_name . '_' . $model->employee->contact->last_name . '.' . $model->manifest->date_run . '.pdf');
    }

    public function printMass(Request $req, $manifestIds) {
        $storagepath = storage_path() . '/app/public/';
        $foldername = 'manifests.' . time();
        mkdir($storagepath . $foldername);
        $path = $storagepath . $foldername . '/';
        $files = array();

        $zip = new ZipArchive();
        $zipfile = $storagepath . $foldername . '.zip';
        $zip->open($zipfile, ZipArchive::CREATE);

        $toBeUnlinked = array();

        foreach(explode(',', $manifestIds) as $manifestId) {
            $manifestModelFactory = new Manifest\ManifestModelFactory();
            $model = $manifestModelFactory->GetById($manifestId);

            if($req->user()->cannot('view', $model->manifest))
                abort(403);

            $filename = $model->employee->contact->first_name . '.' . $model->employee->contact->last_name . '.' . 'manifest.' . $model->manifest->end_date . '.pdf';
            $without_bills = isset($req->without_bills);
            $pdf = PDF::loadView('manifests.manifest_pdf_layout', compact('model', 'without_bills'));
            $pdf->save($path . $filename);
            $zip->addFile($path . $filename, $filename);
            $toBeUnlinked[$manifestId] = $path . $filename;
        }

        $zip->close();

        foreach($toBeUnlinked as $file)
            unlink($file);
        rmdir($storagepath . $foldername);

        return \Response::download($zipfile);
    }

    public function store(Request $req) {
        if($req->user()->cannot('create', Manifest::class))
            abort(403);

        DB::beginTransaction();
        $validationRules = ['employees' => 'required|array|min:1', 'start_date' => 'required|date', 'end_date' => 'required|date|after:' . $req->start_date];
        $validationMessages = ['employees.required' => 'You must select at least one driver to manifest'];

        $this->validate($req, $validationRules, $validationMessages);

        $manifestRepo = new Repos\ManifestRepo();

        $start_date = (new \DateTime($req->start_date))->format('Y-m-d');
        $end_date = (new \DateTime($req->end_date))->format('Y-m-d');

        $manifestRepo->Create($req->employees, $start_date, $end_date);

        DB::commit();

        return;
    }
}

?>
