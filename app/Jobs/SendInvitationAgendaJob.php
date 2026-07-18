<?php

namespace App\Jobs;

use App\Mail\InvitationAgendaResponsableMail;
use App\Models\Appel;
use App\Models\Document;
use App\Models\Prospect;
use App\Models\RendezVous;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendInvitationAgendaJob implements ShouldQueue
{
    use Queueable;

    /**
     * Délai laissé à Ringover pour mettre l'enregistrement audio à disposition
     * avant d'envoyer quand même l'invitation (sans enregistrement).
     */
    private const DELAI_MAX_ATTENTE_AUDIO_MINUTES = 120;

    public function handle(): void
    {
        $rdvs = RendezVous::sansInvitation()
            ->with(['rdvable', 'commercial'])
            ->get()
            ->filter(fn (RendezVous $rdv) => $rdv->rdvable instanceof Prospect && $rdv->commercial?->email);

        $envoyes = 0;

        foreach ($rdvs as $rdv) {
            /** @var Prospect $prospect */
            $prospect = $rdv->rdvable;

            $audioPath = $this->resolveAudioPath($prospect, $rdv);
            $delaiDepasse = $rdv->created_at->diffInMinutes(now()) >= self::DELAI_MAX_ATTENTE_AUDIO_MINUTES;

            if (! $audioPath && ! $delaiDepasse) {
                // On attend encore la synchronisation Ringover de l'enregistrement.
                continue;
            }

            $fichePath = $this->resolveFichePath($prospect, $rdv);

            Mail::to($rdv->commercial->email)->queue(
                new InvitationAgendaResponsableMail($prospect, $rdv, $fichePath, $audioPath)
            );

            $rdv->envoyerInvitation();
            $envoyes++;

            Log::info("Invitation agenda envoyée pour RDV #{$rdv->id}", [
                'fiche_jointe' => (bool) $fichePath,
                'audio_joint' => (bool) $audioPath,
                'envoyee_sans_audio' => ! $audioPath && $delaiDepasse,
            ]);
        }

        Log::info("SendInvitationAgendaJob : {$envoyes} invitation(s) envoyée(s)");
    }

    private function resolveAudioPath(Prospect $prospect, RendezVous $rdv): ?string
    {
        $appel = Appel::where('appelable_type', Prospect::class)
            ->where('appelable_id', $prospect->id)
            ->whereNotNull('enregistrement_audio')
            ->where('date_heure', '>=', $rdv->created_at->subMinutes(10))
            ->latest('date_heure')
            ->first();

        return $appel?->enregistrement_audio;
    }

    private function resolveFichePath(Prospect $prospect, RendezVous $rdv): ?string
    {
        $document = Document::where('documentable_type', Prospect::class)
            ->where('documentable_id', $prospect->id)
            ->where('created_at', '>=', $rdv->created_at->subMinutes(10))
            ->latest('created_at')
            ->first();

        if (! $document) {
            return null;
        }

        $absolutePath = Storage::path($document->path);

        return file_exists($absolutePath) ? $absolutePath : null;
    }
}
