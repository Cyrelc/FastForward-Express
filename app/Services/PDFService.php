<?php

namespace App\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
use ZipArchive;

/**
 * Renders views (['body' => html, 'header' => html?, 'footer' => html?]) to PDF with Browsershot.
 */
class PDFService {
    private const DEFAULT_OPTIONS = [
        'format' => 'Letter',
        'margins' => [20, 10, 20, 10],
        'showBrowserHeaderAndFooter' => true
    ];

    /**
     * Renders each view to its own PDF and merges them, in order, into one. Returns the merged PDF's contents.
     */
    public function create($fileName, $views, $options = []) {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);
        $tmpFolder = $this->makeTempFolder();

        try {
            $pdfMerger = PDFMerger::init();
            $pdfMerger->setFileName($fileName);

            foreach($views as $key => $view)
                $pdfMerger->addPDF($this->renderToFile($view, $tmpFolder . $key . '.pdf', $options));

            $pdfMerger->merge();

            return $pdfMerger->output();
        } finally {
            $this->cleanUp($tmpFolder);
        }
    }

    /**
     * Renders each view to its own PDF and packs them into a zip, one file per view, named by the view's key.
     * Returns the zip's path: send it with response()->download(...)->deleteFileAfterSend(true).
     */
    public function createZip($views, $options = []) {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);
        $tmpFolder = $this->makeTempFolder();
        $zipPath = tempnam(sys_get_temp_dir(), 'zip');

        try {
            $zip = new ZipArchive();
            if($zip->open($zipPath, ZipArchive::OVERWRITE) !== true)
                throw new RuntimeException('Unable to create zip file ' . $zipPath);

            foreach($views as $name => $view) {
                $name = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$name) ?: Str::random(8);
                $zip->addFile($this->renderToFile($view, $tmpFolder . $name . '.pdf', $options), $name . '.pdf');
            }

            // Zip reads the added files on close, so this must happen before they're cleaned up
            if(!$zip->close())
                throw new RuntimeException('Unable to write zip file ' . $zipPath);

            return $zipPath;
        } catch (\Throwable $e) {
            @unlink($zipPath);
            throw $e;
        } finally {
            $this->cleanUp($tmpFolder);
        }
    }

    public function createAsUnifiedHtml($fileName, $views, $options = []) {
        $defaultOptions = [
            'format' => 'Letter',
            'margins' => [0, 10, 20, 10],
            'showBrowserHeaderAndFooter' => true
        ];
        $options = array_merge($defaultOptions, $options);

        $htmlDocument = '';
        foreach($views as $key => $view) {
            if(array_key_exists('header', $view))
                $htmlDocument .= $view['header'];
            $htmlDocument .= $view['body'];
        }

        $browserShot = Browsershot::html($htmlDocument)
            ->format('Letter')
            ->showBackground(true)
            ->margins(...$options['margins']);

        if(array_key_exists('landscape', $options))
            $browserShot->landscape();
        if(array_key_exists('header', $view) || array_key_exists('footer', $view))
            $browserShot->showBrowserHeaderAndFooter();
        if(array_key_exists('header', $view)) {
            $browserShot->marginTop('0mm');
            $browserShot->headerHtml('<span></span>');
        }
        if(array_key_exists('footer', $view))
            $browserShot->footerHtml($view['footer']);

        if(config('app.chrome_path') != null)
            $browserShot->setChromePath(config('app.chrome_path'));

        return $browserShot->pdf('testName');
    }

    private function renderToFile($view, $path, $options) {
        $browserShot = Browsershot::html($view['body'])
            ->format('Letter')
            ->showBackground(true)
            ->margins(...$options['margins']);

        if(array_key_exists('landscape', $options))
            $browserShot->landscape();
        if(array_key_exists('header', $view) || array_key_exists('footer', $view))
            $browserShot->showBrowserHeaderAndFooter();
        if(array_key_exists('header', $view))
            $browserShot->headerHtml($view['header']);
        if(array_key_exists('footer', $view))
            $browserShot->footerHtml($view['footer']);

        if(config('app.chrome_path') != null)
            $browserShot->setChromePath(config('app.chrome_path'));

        $browserShot->save($path);

        return $path;
    }

    /**
     * A folder of its own per run. Named to the second alone, two runs in the same second shared one, so the second
     * mkdir failed - and whichever run finished first deleted the other's files.
     */
    private function makeTempFolder() {
        $folder = storage_path('temp/' . (new \DateTime())->format('Y_m_d_H-i-s_') . Str::random(8) . '/');
        mkdir($folder, 0777, true);

        return $folder;
    }

    private function cleanUp($tempFolder) {
        if (!is_dir($tempFolder)) {
            throw new InvalidArgumentException("$tempFolder must be a directory");
        }
        if (substr($tempFolder, strlen($tempFolder) - 1, 1) != '/') {
            $tempFolder .= '/';
        }
        $files = glob($tempFolder . '*', GLOB_MARK);
        foreach ($files as $file)
            unlink($file);

        rmdir($tempFolder);
    }
}

?>
