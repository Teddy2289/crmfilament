<?php

namespace App\Filament\NsConseil\Pages;

use App\Enums\EventResult;
use App\Enums\EventType;
use App\Enums\ProspectStatut;
use App\Enums\StatutCampagneProspection;
use App\Filament\NsConseil\Concerns\HasCallRecording;
use App\Filament\NsConseil\Concerns\HasCallSession;
use App\Filament\NsConseil\Concerns\HasContactQueue;
use App\Filament\NsConseil\Concerns\HasEmailPreview;
use App\Filament\NsConseil\Concerns\HasStatusResult;
use App\Filament\NsConseil\Concerns\HasSubmitResult;
use App\Filament\NsConseil\Concerns\HasWorkflowHelpers;
use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\PipelineStatut;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\StatutPhoning;
use App\Models\User;
use App\Mail\ConfirmationRdvProspectMail;
use App\Mail\ContactSansCSEMail;
use App\Mail\GenericProspectionMail;
use App\Mail\PreviewableProspectionMail;
use App\Mail\PriseContactBlocMail;
use Illuminate\Mail\Mailable;
use App\Services\Aopia\FicheGenerationService;
use App\Services\Crm\CrmProfileService;
use App\Services\Crm\CrmSettingsService;
use App\Services\Crm\PipelineStatutService;
use App\Services\Phoning\PhoningContactResolver;
use App\Services\Phoning\PhoningContactSearchService;
use App\Services\Phoning\PhoningQueueBuilder;
use App\Services\ProspectionMailService;
use App\Support\CsePhoningWorkflow;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class PhoningWorkflow extends Page
{
    use HasContactQueue;
    use HasCallSession;
    use HasStatusResult;
    use HasEmailPreview;
    use HasCallRecording;
    use HasSubmitResult;
    use HasWorkflowHelpers;

    // protected static ?string $navigationIcon    = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationLabel = 'Flux de travail téléphonique';

    protected static ?string $title = 'Flux de travail téléphonique';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 3;

    // protected static ?int    $navigationSort    = 2;
    protected static string  $view              = 'filament.ns-conseil.pages.phoning-workflow';
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Page accessible via URL pour les boutons de lancement d'appels
    }

    public string $contactType = '';

    public array $currentContactData = [];

    public ?string $ringoverDialedPhone = null;

    // ── Mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        $user = Auth::user();

        $this->isSupervisorMode = app(CrmProfileService::class)
            ->userHasCapability($user, 'supervisor');

        $this->supervisedUserId = $user?->id;

        // Filtrer sur une campagne spécifique si passée en URL
        if ($campagneId = request()->query('campagne_id')) {
            $this->currentCampagneId = (int) $campagneId;
            $this->campagneFiltreId = (int) $campagneId;
        }

        // Configurer le contact demandé avant loadQueue() pour que
        // ensureRequestedContactPriority() soit appelé au bon moment
        if ($contactId = request()->query('contact_id')) {
            $this->requestedContactId = (int) $contactId;
            $this->requestedContactType = request()->query('contact_type', 'prospect');
        }

        $this->loadQueue();
    }
    // ── Requête centrale téléprospecteurs ────────────────────────────
    // Double critère : rôle Spatie OU role_cache pour couvrir les deux cas
    #[\Livewire\Attributes\On('ringover-call')]
    public function captureRingoverDialedPhone(?string $phone = null): void
    {
        $phone = preg_replace('/[^0-9+]/', '', (string) $phone);
        if (! $phone) {
            return;
        }

        $this->ringoverDialedPhone = $phone;
        $this->incomingCallPhone = $this->incomingCallPhone ?: $phone;
    }

    // ── Enregistrement ────────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            // Actions principales visibles
            // "Choisir une campagne" / "Toutes les campagnes" changent le
            // périmètre de TOUTE la file (potentiellement celle d'un autre
            // téléprospecteur en mode supervision) : réservé aux superviseurs
            // et admins, un téléprospecteur travaille sa file telle qu'assignée.
            Action::make('choisir_campagne')
                ->label('Choisir une campagne')
                ->icon('heroicon-o-megaphone')
                ->color('primary')
                ->visible(fn () => $this->isSupervisorMode)
                ->form([
                    \Filament\Forms\Components\Select::make('campagne_id')
                        ->label('Campagne')
                        ->options(function () {
                            $userId = $this->supervisedUserId ?? Auth::id();

                            return CampagnePhoning::active()
                                ->forUser($userId)
                                ->get()
                                ->mapWithKeys(fn($c) => [$c->id => "{$c->nom} ({$c->countContacts()} contacts)"]);
                        })
                        ->required()
                        ->searchable(),
                ])
                ->action(fn(array $data) => $this->selectCampagne((int) $data['campagne_id'])),

            Action::make('toutes_campagnes')
                ->label('Toutes les campagnes')
                ->icon('heroicon-o-squares-2x2')
                ->color('secondary')
                ->visible(fn() => $this->isSupervisorMode && $this->currentCampagneId !== null)
                ->action(fn() => $this->clearCampagne()),

            Action::make('refresh')
                ->label('Rafraîchir')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshQueue()),

            // Groupe d'actions dans un dropdown — outils de configuration/
            // pilotage (back-office, paramétrage CSE v2) réservés aux
            // superviseurs/admins, pas au téléprospecteur qui appelle.
            ActionGroup::make([
                Action::make('voir_campagne')
                    ->label('Statistiques campagne')
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->visible(fn() => $this->currentCampagneId !== null)
                    ->url(fn() => CampagnePhoningResource::getUrl('view', ['record' => $this->currentCampagneId]))
                    ->openUrlInNewTab(),

                Action::make('workflow_cse')
                    ->label('Parcours CSE v2')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn() => WorkflowProspectionCse::getUrl())
                    ->openUrlInNewTab(),

                Action::make('statuts_cse')
                    ->label('Statuts CSE v2')
                    ->icon('heroicon-o-tag')
                    ->color('info')
                    ->url(fn() => StatutsAppelsCse::getUrl())
                    ->openUrlInNewTab(),

                Action::make('back_office')
                    ->label('Prioriser la file')
                    ->icon('heroicon-o-queue-list')
                    ->color('warning')
                    ->url(fn() => route('filament.ns-conseil.pages.phoning-back-office')),
            ])
                ->label('Outils')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->visible(fn () => $this->isSupervisorMode)
                ->dropdownPlacement('bottom-end'),
        ];
    }
}
