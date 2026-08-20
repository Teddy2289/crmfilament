<?php
namespace App\Mail;

use App\Models\Appel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FichePdfResendMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appel $appel,
        public string $destinataire,
    ) {}

    public function build()
    {
        $type = ucfirst((string) ($this->appel->fiche_type ?: 'prospection'));
        $contact = $this->appel->appelable?->nom ?? $this->appel->appelable?->raison_sociale ?? 'votre contact';
        $mail = $this->subject("Renvoi de la fiche {$type} - {$contact}")
            ->view('emails.fiche-pdf-resend', [
                'appel' => $this->appel,
                'type' => $type,
                'contact' => $contact,
            ]);

        $path = $this->fichePdfPathAbsolu();
        if ($path && is_file($path)) {
            $mail->attach($path, [
                'as' => 'Fiche_' . ucfirst((string) $this->appel->fiche_type) . '_' . $this->appel->id . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    public function fichePdfPathAbsolu(): ?string
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
