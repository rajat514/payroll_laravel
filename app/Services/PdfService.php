<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\Config\FontVariables;
use Mpdf\Config\ConfigVariables;

class PdfService
{
    /**
     * Render a Blade view into a PDF binary using mPDF with Indic script support.
     */
    public function renderView(string $view, array $data = [], array $options = []): string
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf(array_merge([
            'mode' => 'utf-8',
            'tempDir' => storage_path('framework/mpdf'),
            'format' => 'A4',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'fontDir' => array_merge($fontDirs, [public_path('fonts')]),
            'fontdata' => $fontData + [
                // Map Devanagari-capable fonts
                'mangal' => [
                    'R' => 'MANGAL.TTF',
                    'B' => 'MANGAL.TTF',
                ],
                'notosansdevanagari' => [
                    'R' => 'NotoSansDevanagari-Regular.ttf',
                    'B' => 'NotoSansDevanagari-Bold.ttf',
                ],
            ],
            // Use a Latin-friendly default; mPDF will auto-switch to Devanagari fonts
            'default_font' => 'dejavusans',
        ], $options));

        $html = view($view, $data)->render();
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S'); // return as string
    }
}


