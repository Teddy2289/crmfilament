<?php
namespace App\Mail;

use App\Models\Appel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FicheVerteCommercialMail extends Mailable
{
    use Queueable, SerializesModels;

    public Appel $appel;

    public function __construct(Appel $appel)
    {
        $this->appel = $appel;
    }

    public function build()
    {
        $objet = "Fiche Verte - RDV à conclure pour l'appel du {$this->appel->date_heure->format('d/m/Y')}";
        $mail = $this->subject($objet)
            ->markdown('emails.fiche-verte-commercial')
            ->with([
                'appel' => $this->appel,
            ]);

        $fichePath = $this->fichePdfPathAbsolu();
        if ($fichePath && file_exists($fichePath)) {
            $mail->attach($fichePath, [
                'as' => 'Fiche_Verte_' . $this->appel->id . '_' . now()->format('Ymd') . '.pdf',
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
