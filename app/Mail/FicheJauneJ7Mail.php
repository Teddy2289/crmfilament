<?php
namespace App\Mail;

use App\Models\Appel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FicheJauneJ7Mail extends Mailable
{
    use Queueable, SerializesModels;

    public Appel $appel;
    public ?User $destinataire;

    public function __construct(Appel $appel, ?User $destinataire = null)
    {
        $this->appel = $appel;
        $this->destinataire = $destinataire;
    }

    public function build()
    {
        $objet = "Fiche Jaune J+7 - Rappel Commercial pour l'appel du {$this->appel->date_heure->format('d/m/Y')}";
        $mail = $this->subject($objet)
            ->markdown('emails.fiche-jaune-j7')
            ->with([
                'appel' => $this->appel,
                'destinataire' => $this->destinataire,
            ]);

        $fichePath = $this->fichePdfPathAbsolu();
        if ($fichePath && file_exists($fichePath)) {
            $mail->attach($fichePath, [
                'as' => 'Fiche_Jaune_J+7_' . $this->appel->id . '_' . now()->format('Ymd') . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    /** Retourne uniquement un PDF généré dans storage/app/public/fiches-pdf. */
    protected function fichePdfPathAbsolu(): ?string
    {
        $path = $this->appel->fiche_word_path;
        if (blank($path)) {
            return null;
        }

        $normalized = trim((string) $path);
        if (str_contains($normalized, '://')) {
            $normalized = parse_url($normalized, PHP_URL_PATH) ?: $normalized;
        }
        $normalized = ltrim($normalized, '/');
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        if (! str_starts_with($normalized, 'fiches-pdf/') || ! Str::endsWith(Str::lower($normalized), '.pdf')) {
            return null;
        }

        return Storage::disk('public')->path($normalized);
    }
}
