<?php

namespace App\Services;

use DB;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Browsershot\Browsershot;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class PDFService {
    public function create($fileName, $pdfFiles, $options = []) {
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

        foreach($pdfFiles as $key => $file) {
            $fileName = ($tmpFolder . $key . '.pdf');
            $browserShot = Browsershot::html($file['body'])
                ->format('Letter')
                ->showBackground(true)
                ->margins(...$options['margins']);

            if(array_key_exists('landscape', $options))
                $browserShot->landscape();
            if(array_key_exists('header', $file) || array_key_exists('footer', $file))
                $browserShot->showBrowserHeaderAndFooter();
            if(array_key_exists('header', $file))
                $browserShot->headerHtml($file['header']);
            if(array_key_exists('footer', $file))
                $browserShot->footerHtml($file['footer']);

            if(config('app.chrome_path') != null)
                $browserShot->setChromePath(config('app.chrome_path'));

            $browserShot->save($fileName);

            $pdfMerger->addPDF($fileName);
        }

        $pdfMerger->merge();

        $this->cleanUp($tmpFolder);

        return $pdfMerger->output();
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
