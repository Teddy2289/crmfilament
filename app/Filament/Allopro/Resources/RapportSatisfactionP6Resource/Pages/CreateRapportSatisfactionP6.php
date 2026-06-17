<?php

namespace App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages;

use App\Enums\TicketStatut;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource;
use App\Models\Ticket;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRapportSatisfactionP6 extends CreateRecord
{
    protected static string $resource = RapportSatisfactionP6Resource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['operateur_id'] = $data['operateur_id'] ?? auth()->id();

        return $data;
    }

    /**
     * Handle record creation with proper relationship loading
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Create the record first
        $record = static::getModel()::create($data);

        // Load relationships after creation to avoid null issues
        $record->load(['ticket', 'artisan']);

        return $record;
    }

    /**
     * After create actions with proper error handling
     */
    protected function afterCreate(): void
    {
        try {
            $record = $this->getRecord();

            // Ensure relationships are loaded
            if (! $record->relationLoaded('ticket')) {
                $record->load('ticket');
            }

            // Safely check ticket existence and statut
            if ($record->ticket && $record->ticket->statut) {
                $this->updateTicketStatut($record);
            } else {
                // Log or handle missing ticket
                \Log::warning('Rapport P6 créé sans ticket associé', [
                    'rapport_id' => $record->id,
                    'ticket_id' => $record->ticket_id,
                ]);
            }

            // Send notification for P8 if needed
            $this->checkAndSendP8Notification($record);

        } catch (\Exception $e) {
            // Log the error but don't break the creation process
            \Log::error('Erreur après création rapport P6: '.$e->getMessage(), [
                'rapport_id' => $record->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            // Send error notification to user
            Notification::make()
                ->title('⚠️ Rapport créé mais action post-création partiellement échouée')
                ->body('Le rapport a été créé mais la mise à jour du ticket a rencontré une erreur.')
                ->warning()
                ->send();
        }
    }

    /**
     * Update ticket status based on NPS score
     */
    protected function updateTicketStatut($record): void
    {
        // Check if ticket is in the correct state
        if ($record->ticket->statut !== TicketStatut::InterventionRealisee) {
            \Log::info('Ticket non mis à jour - statut incorrect', [
                'ticket_id' => $record->ticket->id,
                'current_statut' => $record->ticket->statut,
                'expected_statut' => TicketStatut::InterventionRealisee->value,
            ]);

            return;
        }

        // Determine new status based on NPS
        $nouveauStatut = match (true) {
            $record->note_nps >= 8 => TicketStatut::ClotureSatisfait,
            $record->note_nps >= 6 => TicketStatut::SuiviQualiteRequis,
            default => TicketStatut::ReclamationOuverte,
        };

        try {
            // Change ticket status
            $record->ticket->changerStatut(
                $nouveauStatut,
                "NPS {$record->note_nps}/10 — Rapport P6 saisi le ".now()->format('d/m/Y')
            );

            // Success notification for status change
            Notification::make()
                ->title('✅ Ticket mis à jour')
                ->body('Le statut du ticket a été changé en: '.$nouveauStatut->label())
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Log::error('Erreur lors du changement de statut du ticket', [
                'ticket_id' => $record->ticket->id,
                'target_statut' => $nouveauStatut,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('⚠️ Erreur mise à jour ticket')
                ->body('Impossible de mettre à jour le statut du ticket: '.$e->getMessage())
                ->warning()
                ->send();
        }
    }

    /**
     * Check NPS and send P8 notification if needed
     */
    protected function checkAndSendP8Notification($record): void
    {
        if ($record->note_nps <= 5) {
            Notification::make()
                ->title('🚨 NPS ≤ 5 — Réclamation P8 ouverte automatiquement')
                ->body('Délai de résolution : 5 jours ouvrés.')
                ->danger()
                ->persistent()
                ->send();

            // Log P8 creation
            \Log::info('P8 automatique créée suite à rapport P6', [
                'rapport_id' => $record->id,
                'ticket_id' => $record->ticket_id,
                'nps_score' => $record->note_nps,
            ]);
        }
    }

    /**
     * Redirect to view page after creation
     */
    protected function getRedirectUrl(): string
    {
        try {
            return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
        } catch (\Exception $e) {
            // Fallback to index if view fails
            return $this->getResource()::getUrl('index');
        }
    }

    /**
     * Add custom validation before create
     */
    protected function beforeCreate(): void
    {
        // Validate that ticket exists and is in correct state
        $ticketId = $this->data['ticket_id'] ?? null;

        if ($ticketId) {
            $ticket = Ticket::find($ticketId);

            if (! $ticket) {
                Notification::make()
                    ->title('Erreur')
                    ->body('Le ticket sélectionné n\'existe pas.')
                    ->danger()
                    ->send();
                $this->halt();
            }

            if ($ticket->statut !== TicketStatut::InterventionRealisee) {
                Notification::make()
                    ->title('⚠️ Attention')
                    ->body('Le ticket doit être en statut "Intervention réalisée" pour créer un rapport P6.')
                    ->warning()
                    ->send();
                // Don't halt, just warn
            }
        }
    }
}
