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

        $mail = $this->subject($objet)
            ->markdown('emails.fiche-verte-commercial')
            ->with([
                'appel' => $this->appel,
            ]);

        $fichePath = $this->getLocalFichePath();
        if ($fichePath && file_exists($fichePath)) {
            $mail->attach($fichePath, [
                'as' => 'Fiche_Verte_' . $this->appel->id . '_' . now()->format('Ymd') . '.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        return $mail;
    }

    /**
     * Récupère le chemin local du fichier fiche Word.
     * Accepte les chemins publics URL, les chemins de disque public relatifs
     * et les chemins localhost de tests.
     */
    protected function getLocalFichePath(): ?string
    {
        $path = $this->appel->fiche_word_path;

        if (blank($path)) {
            return null;
        }

        $normalized = trim((string) $path);

        if (str_contains($normalized, '://')) {
            $normalized = parse_url($normalized, PHP_URL_PATH) ?: $normalized;
        }

        if (str_starts_with($normalized, '/storage/')) {
            $normalized = Str::after($normalized, '/storage/');
        } elseif (str_starts_with($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        if (! str_starts_with($normalized, 'fiches/')) {
            $normalized = ltrim($normalized, '/');
        }

        return Storage::disk('public')->path($normalized);
    }
}
