<?php

namespace App\Mail;

use App\Models\Appel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FicheJauneJ7Mail extends Mailable
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
        $objet = "Fiche Jaune J+7 - Rappel Commercial pour l'appel du {$this->appel->date_heure->format('d/m/Y')}";

        return $this->subject($objet)
            ->view('emails.fiche-jaune-j7')
            ->with([
                'appel' => $this->appel,
            ])
            ->attach(storage_path('app/' . $this->appel->fiche_word_path), [
                'as' => 'Fiche_Jaune_J+7_' . $this->appel->id . '_' . now()->format('Ymd') . '.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }
}