<?php

namespace App\Services;

use Dompdf\Dompdf;
use Illuminate\Http\Response;

class PdfService
{
    public function download(string $view, array $data, string $filename): Response
    {
        $dompdf = new Dompdf(['isRemoteEnabled' => false]);

        $dompdf->loadHtml(view($view, $data)->render());
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
