<?php

namespace App\Mail;

use App\Models\Appel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FicheVerteCommercialMail extends Mailable
{
    use Queueable, SerializesModels;

    public Appel $appel;

    /**
     * Create a new message instance.
     */
    public function __construct(Appel $appel)
    {
        $this->appel = $appel;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $objet = "Fiche Verte - RDV à conclure pour l'appel du {$this->appel->date_heure->format('d/m/Y')}";

        return $this->subject($objet)
            ->markdown('emails.fiche-verte-commercial')
            ->with([
                'appel' => $this->appel,
            ])
            ->attach($this->getLocalFichePath(), [
                'as' => 'Fiche_Verte_' . $this->appel->id . '_' . now()->format('Ymd') . '.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }

    /**
     * Récupère le chemin local du fichier fiche Word.
     * L'URL stockée est du type: /storage/fiches/2026/08/fiche-verte-*.docx
     * On doit retourner le chemin complet: storage/app/public/fiches/2026/08/fiche-verte-*.docx
     */
    protected function getLocalFichePath(): string
    {
        // Extraire le chemin relatif du disque public depuis l'URL
        // URL exemple: https://domain.com/storage/fiches/2026/08/fiche.docx
        // ou: /storage/fiches/2026/08/fiche.docx
        $url = $this->appel->fiche_word_path;
        
        // Extraire la partie après /storage/
        $relativePath = Str::after($url, '/storage/');
        
        // Retourner le chemin complet dans le disque public
        return Storage::disk('public')->path($relativePath);
    }
}
