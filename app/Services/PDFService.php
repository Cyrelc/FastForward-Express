<?php

namespace App\Services;

use DB;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Browsershot\Browsershot;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class PDFService {
    public function create($fileName, $views, $options = []) {
        $start = microtime(true);

        $timeStamps = [];
        $defaultOptions = [
            'format' => 'Letter',
            'margins' => [20, 10, 20, 10],
            'showBrowserHeaderAndFooter' => true
        ];
        $options = array_merge($defaultOptions, $options);
        //sanitize the requested file name jic
        $tmpFolder = storage_path() . '/temp/' . (new \DateTime())->format('Y_m_d_H-i-s/');
        mkdir($tmpFolder, 0777, true);

        $pdfMerger = PDFMerger::init();
        $pdfMerger->setFileName($fileName);

        foreach($views as $key => $view) {
            $fileProcessStart = microtime(true);
            $browserShotStart = microtime(true);
            $fileName = ($tmpFolder . $key . '.pdf');
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

            $browserShot->save($fileName);
            $browserShotEnd = microtime(true);

            $pdfMergerStart = microtime(true);
            $pdfMerger->addPDF($fileName);
            $pdfMergerEnd = microtime(true);
            $fileProcessEnd = microtime(true);

            $timeStamps[$key] = [
                'fileProcessTime' => $fileProcessEnd - $fileProcessStart,
                'browserShotTime' => $browserShotEnd - $browserShotStart,
                'pdfMergerTime' => $pdfMergerEnd - $pdfMergerStart
            ];
        }

        $pdfMerger->merge();

        $this->cleanUp($tmpFolder);
        $end = microtime(true);
        $timeStamps['overall'] = $end - $start;
        $generateOutputStart = microtime(true);
        $pdfMerger->output();
        $generateOutputEnd = microtime(true);
        $timeStamps['generateOutput'] = $generateOutputEnd - $generateOutputStart;

        return $pdfMerger->output();
    }

    public function createAsUnifiedHtml($fileName, $views, $options = []) {
        $defaultOptions = [
            'format' => 'Letter',
            'margins' => [0, 10, 20, 10],
            'showBrowserHeaderAndFooter' => true
        ];
        $options = array_merge($defaultOptions, $options);
        //sanitize the requested file name jic
        $tmpFolder = storage_path() . '/temp/' . (new \DateTime())->format('Y_m_d_H-i-s/');
        mkdir($tmpFolder, 0777, true);

        $pdfMerger = PDFMerger::init();
        $pdfMerger->setFileName($fileName);

        $htmlDocument = '';
        foreach($views as $key => $view) {
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
