<?php

namespace App\Http\Controllers;

use DB;
use View;
use ZipArchive;
use Response;

use App\Http\Repos;
use App\Http\Models\Manifest;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Browsershot\Browsershot;

use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;


class ManifestController extends Controller {
    private $storagePath;

    public function __construct() {
        $this->middleware('auth');

        $this->storagePath = storage_path() . '/manifests/' . (new \DateTime())->format('Y_m_d_H-i-s/');
    }

    public function delete(Request $req, $manifestId) {
        $manifestRepo = new Repos\ManifestRepo();
        $manifest = $manifestRepo->GetById($manifestId);
        if($req->user()->cannot('delete', $manifest))
            abort(403);

        DB::beginTransaction();

        $manifestRepo->delete($manifestId);

        DB::commit();

        return response()->json([
            'success' => true
        ]);
    }

    public function download(Request $req, $manifestIds) {
        $manifestIds = explode(',', $manifestIds);

        if(count($manifestIds) > 50)
            abort(413, 'Currently unable to package more than 50 manifests at a time. Please select 50 or fewer and try again. Aplogies for any inconvenience');

        $files = $this->preparePdfs($req, $manifestIds);

        $zipArchive = new ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');
        $zipArchive->open($tempFile, ZipArchive::CREATE);

        foreach($files as $name => $file)
            $zipArchive->addFile($file, $name);

        $zipArchive->close();

        $this->cleanPdfs($files);

        return response()->download($tempFile, 'manifests-' . time() . '.zip')->deleteFileAfterSend(true);
    }

    public function getDriversToManifest(Request $req) {
        if($req->user()->cannot('create', Manifest::class))
            abort(403);

        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $drivers = $manifestModelFactory->GetDriverListModel($req);

        return json_encode($drivers);
    }

    public function getModel(Request $req, $manifestId) {
        $manifestRepo = new Repos\ManifestRepo();
        $manifest = $manifestRepo->GetById($manifestId);

        if(!$manifest)
            abort(404);

        if($req->user()->cannot('view', $manifest))
            abort(403);

        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->GetById($req->user(), $manifestId);

        return json_encode($model);
    }

    public function index(Request $req) {
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

        $queryRepo = new Repos\QueryRepo();
        $queries = $queryRepo->GetByTable('manifests');

        return response()->json([
            'succcess' => true,
            'data' => $model,
            'queries' => $queries
        ]);
    }

    public function print(Request $req, $manifestIds) {
        $manifestIds = explode(',', $manifestIds);

        if(count($manifestIds) > 50)
            abort(413, 'Currently unable to package more than 50 manifests at a time. Please select 50 or fewer and try again. Apologies for any inconvenience');

        $files = $this->preparePdfs($req, $manifestIds);

        $pdfMerger = PDFMerger::init();

        foreach($files as $file)
            $pdfMerger->addPDF($file);

        $pdfMerger->merge();

        $this->cleanPdfs($files);

        $fileName = (count($files) > 1 ? 'Manifests.' . time() : array_key_first($files)) . '.pdf';
        $fileName = str_replace('&', '', $fileName);

        return response($pdfMerger->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename=' . $fileName);
    }

    public function regather(Request $req, $manifestId) {
        if($req->user()->cannot('create', Manifest::class))
            abort(403);

        $manifestRepo = new Repos\ManifestRepo();

        DB::beginTransaction();

        $manifestRepo->Regather($manifestId);

        DB::commit();

        $manifestModelFactory = new Manifest\ManifestModelFactory();
        $model = $manifestModelFactory->GetById($req->user(), $manifestId);

        return json_encode($model);
    }

    public function store(Request $req) {
        if($req->user()->cannot('create', Manifest::class))
            abort(403);

        $validationRules = ['employees' => 'required|array|min:1', 'start_date' => 'required|date', 'end_date' => 'required|date|after:' . $req->start_date];
        $validationMessages = ['employees.required' => 'You must select at least one driver to manifest'];

        $this->validate($req, $validationRules, $validationMessages);

        $manifestRepo = new Repos\ManifestRepo();

        DB::beginTransaction();

        $startDate = (new \DateTime($req->start_date))->format('Y-m-d');
        $endDate = (new \DateTime($req->end_date))->format('Y-m-d');

        $manifestRepo->Create($req->employees, $startDate, $endDate);

        DB::commit();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Private functions
     */

    private function cleanPdfs($files) {
        foreach($files as $file)
            unlink($file);
        rmdir($this->storagePath);

        return !is_dir($this->storagePath);
    }

    private function preparePdfs(Request $req, $manifestIds) {
        $manifestModelFactory = new Manifest\ManifestModelFactory();

        $withoutBills = isset($req->without_bills);

        $files = array();
        mkdir($this->storagePath, 0777, true);

        foreach($manifestIds as $manifestId) {
            $model = $manifestModelFactory->GetById($req->user(), $manifestId);

            if($req->user()->cannot('view', $model->manifest))
                abort(403);

            $fileName = $model->employee->contact->first_name . '_' . $model->employee->contact->last_name . '-' . $model->manifest->manifest_id;
            $fileName = preg_replace('/\s+/', '_', $fileName);
            $fileName = preg_replace('/[&.\/\\:*?"<>| ]/', '', $fileName);
            $outputFile = $this->storagePath . $fileName . '.pdf';

            $header = view('manifests.manifest_pdf_header', compact('model'))->render();
            $footer = view('manifests.manifest_pdf_footer')->render();

            Pdf::view('manifests.manifest_pdf', [
                'model' => $model,
                'withoutBills' => $withoutBills
            ])->withBrowsershot(function(Browsershot $browsershot) use ($header, $footer) {
                $browsershot->format('Letter')
                    ->showBackground(true)
                    ->margins(25, 10, 20, 10)
                    ->showBrowserHeaderAndFooter()
                    ->headerHtml($header)
                    ->footerHtml($footer);

                if(config('app.chrome_path') != null) {
                    $browsershot->setChromePath(config('app.chrome_path'));
                }

                return $browsershot;
            })->save($outputFile);

            $files[$fileName] = $outputFile;
        }

        return $files;
    }
}

?>
