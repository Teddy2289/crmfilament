<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class TestFichesPdfMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $ficheBleuePath,
        public string $ficheJaunePath,
        public string $ficheVertePath
    ) {}

    public function build()
    {
        return $this->view('emails.test-fiches-pdf')
            ->subject('Test - Fiches de Prospection PDF')
            ->attach($this->ficheBleuePath, [
                'as' => 'Fiche_Bleue_Test.pdf',
                'mime' => 'application/pdf',
            ])
            ->attach($this->ficheJaunePath, [
                'as' => 'Fiche_Jaune_Test.pdf',
                'mime' => 'application/pdf',
            ])
            ->attach($this->ficheVertePath, [
                'as' => 'Fiche_Verte_Test.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}