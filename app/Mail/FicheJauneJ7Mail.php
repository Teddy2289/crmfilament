<?php

namespace App\Mail;

use App\Models\Appel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FicheJauneJ7Mail extends Mailable
{
    use Queueable, SerializesModels;

    public Appel $appel;

    public ?User $destinataire;

    /**
     * Create a new message instance.
     */
    public function __construct(Appel $appel, ?User $destinataire = null)
    {
        $this->appel = $appel;
        $this->destinataire = $destinataire;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $objet = "Fiche Jaune J+7 - Rappel Commercial pour l'appel du {$this->appel->date_heure->format('d/m/Y')}";

        return $this->subject($objet)
            ->markdown('emails.fiche-jaune-j7')
            ->with([
                'appel' => $this->appel,
                'destinataire' => $this->destinataire,
            ])
            ->attach($this->fichePathAbsolu(), [
                'as' => 'Fiche_Jaune_J+7_' . $this->appel->id . '_' . now()->format('Ymd') . '.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }

    /**
     * fiche_word_path est une URL publique (Storage::disk('public')->url()),
     * pas un chemin disque : il faut retirer le préfixe /storage/ avant de
     * retrouver le fichier sur le disque local.
     */
    protected function fichePathAbsolu(): string
    {
        $relatif = \Illuminate\Support\Str::after($this->appel->fiche_word_path, '/storage/');

        return \Illuminate\Support\Facades\Storage::disk('public')->path($relatif);
    }
}