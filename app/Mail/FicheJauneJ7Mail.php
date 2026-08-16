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

        $mail = $this->subject($objet)
            ->markdown('emails.fiche-jaune-j7')
            ->with([
                'appel' => $this->appel,
                'destinataire' => $this->destinataire,
            ]);

        $fichePath = $this->fichePathAbsolu();
        if ($fichePath && file_exists($fichePath)) {
            $mail->attach($fichePath, [
                'as' => 'Fiche_Jaune_J+7_' . $this->appel->id . '_' . now()->format('Ymd') . '.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        return $mail;
    }

    /**
     * Accepte les chemins de stockage public de type:
     * - /storage/fiches/2026/08/fiche.docx
     * - https://domain.com/storage/fiches/2026/08/fiche.docx
     * - fiches/2026/08/fiche.docx
     */
    protected function fichePathAbsolu(): ?string
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
            $normalized = \Illuminate\Support\Str::after($normalized, '/storage/');
        } elseif (str_starts_with($normalized, 'storage/')) {
            $normalized = \Illuminate\Support\Str::after($normalized, 'storage/');
        }

        if (! str_starts_with($normalized, 'fiches/')) {
            $normalized = ltrim($normalized, '/');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->path($normalized);
    }
}