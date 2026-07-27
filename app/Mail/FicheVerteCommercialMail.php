<?php

namespace App\Mail;

use App\Models\Appel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
            ->attach(storage_path('app/public/' . $this->fichePathRelatif()), [
                'as' => 'Fiche_Verte_' . $this->appel->id . '_' . now()->format('Ymd') . '.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }

    protected function fichePathRelatif(): string
    {
        return \Illuminate\Support\Str::after($this->appel->fiche_word_path, '/storage/');
    }
}
