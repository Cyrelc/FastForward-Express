<?php

namespace App\Http\Controllers;

use DB;
use View;
use ZipArchive;
use Response;

use App\Http\Repos;
use App\Http\Models\Manifest;
use Illuminate\Http\Request;

use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;


class ManifestController extends Controller {
    private $storagePath;
    private $folderName;

    public function __construct() {
        $this->middleware('auth');

        $this->storagePath = storage_path() . '/manifests/';
        $this->folderName = (new \DateTime())->format('Y_m_d_H-i-s');
    }

    public function delete(Request $req, $manifestId) {
        $manifestRepo = new Repos\ManifestRepo();
        $manifest = $manifestRepo->GetById($manifestId);
        if($req->user()->cannot('delete', $manifest))
            abort(403);

        DB::beginTransaction();

        $manifestRepo->delete($manifestId);

        DB::commit();
    }

    public function download(Request $req, $manifestIds) {
        $manifestIds = explode(',', $manifestIds);

        if(count($manifestIds) > 50)
            abort(413, 'Currently unable to package more than 50 manifests at a time. Please select 50 or fewer and try again. Aplogies for any inconvenience');

        $files = $this->preparePdfs($req, $manifestIds);

        if(count($files) === 1)
            return \Response::download($files[array_key_first($files)]);
        else {
            $zip = new ZipArchive();
            $zipFile = $this->storagePath . $this->folderName . '.zip';
            $zip->open($zipFile, ZipArchive::CREATE);

            foreach($files as $name => $file)
                $zip->addFile($file, $name);

            $zip->close();

            $this->cleanPdfs($files);

            return \Response::download($zipFile);
        }
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

        return json_encode($model);
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

        $fileName = $this->storagePath . count($files) > 1 ? 'Manifests.' . time() . '.pdf' : array_key_first($files);
        $fileName = str_replace('&', '', $fileName);

        $this->cleanPdfs($files);

        $pdfMerger->save($fileName, 'inline');

        return response()->file($fileName);
    }

    public function store(Request $req) {
        if($req->user()->cannot('create', Manifest::class))
            abort(403);

        DB::beginTransaction();
        $validationRules = ['employees' => 'required|array|min:1', 'start_date' => 'required|date', 'end_date' => 'required|date|after:' . $req->start_date];
        $validationMessages = ['employees.required' => 'You must select at least one driver to manifest'];

        $this->validate($req, $validationRules, $validationMessages);

        $manifestRepo = new Repos\ManifestRepo();

        $startDate = (new \DateTime($req->start_date))->format('Y-m-d');
        $endDate = (new \DateTime($req->end_date))->format('Y-m-d');

        $manifestRepo->Create($req->employees, $startDate, $endDate);

        DB::commit();

        return;
    }

    /**
     * Private functions
     */

    private function cleanPdfs($files) {
        $path = $this->storagePath . $this->folderName . '/';

        foreach($files as $file)
            unlink($file);
        rmdir($this->storagePath . $this->folderName);

        return !is_dir($path);
    }

    private function preparePdfs(Request $req, $manifestIds) {
        $manifestModelFactory = new Manifest\ManifestModelFactory();

        $withoutBills = isset($req->without_bills);

        $files = array();
        $path = $this->storagePath . $this->folderName . '/';
        mkdir($path, 0777, true);

        foreach($manifestIds as $manifestId) {
            $model = $manifestModelFactory->GetById($req->user(), $manifestId);

            if($req->user()->cannot('view', $model->manifest))
                abort(403);

            $fileName = $model->employee->contact->first_name . '_' . $model->employee->contact->last_name . '-' . $model->manifest->manifest_id;
            $fileName = preg_replace('/\s+/', '_', $fileName);
            $fileName = preg_replace('/[&.\/\\:*?"<>| ]/', '', $fileName);

            $inputFile = $path . $fileName . '.html';
            $outputFile = $path . $fileName . '.pdf';
            $headerFile = $path . $fileName . '-header.html';
            $footerFile = $path . $fileName . '-footer.html';
            $puppeteerScript = resource_path('assets/js/puppeteer/phpPuppeteer.js');

            $file = view('manifests.manifest_pdf', compact('model', 'withoutBills'))->render();
            file_put_contents($inputFile, $file);
            file_put_contents($headerFile, view('manifests.manifest_pdf_header', compact('model'))->render());
            file_put_contents($footerFile, view('manifests.manifest_pdf_footer')->render());

            $options = json_encode([
                'displayHeaderFooter' => true,
                'margin' => [
                    'top' => 80,
                    'bottom' => 70,
                    'left' => 30,
                    'right' => 30
                ],
                'path' => $path . $fileName . '.pdf',
                'printBackground' => true,
            ]);

            $command = 'node ' . $puppeteerScript . ' --file file:' . $inputFile;
            $command .= ' --header ' . $headerFile;
            $command .= ' --footer ' . $footerFile;
            $command .= ' --stylesheet ' . public_path('css/manifest_pdf.css');
            $command .= ' --pdfOptions "' . json_encode($options) . '"';

            exec($command, $output, $returnCode);
            if($returnCode != 0 || !file_exists($outputFile))
                dd($returnCode, $output);

            unlink($inputFile);
            unlink($headerFile);
            unlink($footerFile);

            $files[$fileName] = $path . $fileName . '.pdf';
        }

        return $files;
    }
}

?>
