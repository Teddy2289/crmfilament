<?php

namespace App\Http\Controllers;

use App\Models\Devis;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    public function facture(Facture $facture)
    {
        $pdf = Pdf::loadView('pdf.facture', compact('facture'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('facture-'.$facture->numero.'.pdf');
    }

    public function devis(Devis $devis)
    {
        $pdf = Pdf::loadView('pdf.devis', compact('devis'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('devis-'.$devis->numero.'.pdf');
    }
}
