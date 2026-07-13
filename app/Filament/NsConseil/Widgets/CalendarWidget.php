<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\RendezVousStatut;
use App\Enums\RendezVousType;
use App\Models\RendezVous;
use App\Services\GoogleCalendarService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;


class CalendarWidget extends FullCalendarWidget
{
    protected static bool $isDiscovered = false;

    public bool $showEventModal = false;

    public array $selectedEvent = [];

    public Model|string|null $model = null;


    public function config(): array
    {
        return [
            'firstDay' => 1,
            'locale' => 'fr',
            'height' => 'auto',
            'navLinks' => true,
            'businessHours' => [
                'daysOfWeek' => [1, 2, 3, 4, 5],
                'startTime' => '08:00',
                'endTime' => '19:00',
            ],
            'slotMinTime' => '07:00',
            'slotMaxTime' => '21:00',
            'initialView' => 'timeGridWeek',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            ],
            'eventDisplay' => 'block',
            'nowIndicator' => true,
            'scrollTime' => '08:00',
        ];
    }

    /**
     * Marque chaque événement Google avec son calendrier d'origine et masque
     * ceux dont le calendrier a été désactivé depuis la légende (voir calendar.blade.php).
     */
    public function eventDidMount(): string
    {
        return <<<'JS'
        function({ event, el }) {
            var calName = event.extendedProps.calendar_name;
            if (! calName) {
                return;
            }
            el.setAttribute('data-calendar-name', calName);
            try {
                var hidden = JSON.parse(localStorage.getItem('hiddenGoogleCalendars') || '[]');
                if (hidden.indexOf(calName) !== -1) {
                    el.style.display = 'none';
                }
            } catch (e) {}
        }
        JS;
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $start = Carbon::parse($fetchInfo['start']);
        $end = Carbon::parse($fetchInfo['end']);
        $user = auth()->user();
        $events = [];

        // ── 1. RDV CRM de l'utilisateur ──────────────────────────────
        $rdvs = RendezVous::query()
            ->whereBetween('date_heure', [$start, $end])
            ->where(function ($q) use ($user) {
                $q->where('commercial_id', $user->id)
                    ->orWhere('teleprospecteur_id', $user->id);
            })
            ->with(['rdvable'])
            ->get();

        $syncedGoogleIds = [];

        foreach ($rdvs as $rdv) {
            $events[] = $this->rdvToEvent($rdv);
            if ($rdv->google_event_id) {
                $syncedGoogleIds[$rdv->google_event_id] = true;
            }
        }

        // ── 2. Événements Google Calendar ────────────────────────────────
        try {
            $googleEvents = app(GoogleCalendarService::class)
                ->getEvents($user, $start->toDateTime(), $end->toDateTime());

            foreach ($googleEvents as $gEvent) {
                if (isset($syncedGoogleIds[$gEvent['id']])) {
                    continue;
                }
                $events[] = $this->googleEventToEvent($gEvent);
            }
        } catch (\Throwable $e) {
            Log::warning('CalendarWidget: Google events fetch failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $events;
    }

    // ── Convertisseurs ────────────────────────────────────────────────

    private function rdvToEvent(RendezVous $rdv): array
    {
        $type = $rdv->type instanceof RendezVousType ? $rdv->type : RendezVousType::tryFrom((string) $rdv->type);
        $statut = $rdv->statut instanceof RendezVousStatut ? $rdv->statut : RendezVousStatut::tryFrom((string) $rdv->statut);
        $color = $statut === RendezVousStatut::Annule ? '#9ca3af' : $this->getTypeColor($type);

        $title = $rdv->interlocuteur_nom ?? 'Sans interlocuteur';
        if ($rdv->rdvable) {
            $entite = $rdv->rdvable->nom ?? $rdv->rdvable->nom_tiers ?? null;
            if ($entite) {
                $title .= " — {$entite}";
            }
        }

        return [
            'id' => 'rdv-' . $rdv->id,
            'title' => '[' . ($type?->value ?? '?') . '] ' . $title,
            'start' => $rdv->date_heure->toIso8601String(),
            'end' => $rdv->date_heure->copy()->addHour()->toIso8601String(),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'source' => 'crm',
                'rdv_id' => $rdv->id,
                'type' => $type?->value,
                'statut' => $statut?->value,
                'interlocuteur' => $rdv->interlocuteur_nom,
                'telephone' => $rdv->interlocuteur_tel,
                'lieu' => $rdv->lieu,
                'notes' => $rdv->notes,
                'synced_google' => (bool) $rdv->google_event_id,
            ],
        ];
    }

    private function googleEventToEvent(array $gEvent): array
    {
        $start = $gEvent['start']['dateTime'] ?? $gEvent['start']['date'] ?? null;
        $end = $gEvent['end']['dateTime'] ?? $gEvent['end']['date'] ?? null;
        $allDay = ! isset($gEvent['start']['dateTime']);

        // Priorité : colorId de l'événement > couleur du calendrier > gris défaut
        $color = $this->resolveGoogleColor(
            $gEvent['colorId'] ?? null,
            $gEvent['_calendar_color'] ?? null
        );

        // Couleur de bordure légèrement plus foncée
        $border = $this->darkenHex($color, 20);

        $calName = $gEvent['_calendar_name'] ?? $gEvent['_calendar_id'] ?? 'Google';
        $title = ($gEvent['summary'] ?? 'Sans titre');

        return [
            'id' => 'google-' . $gEvent['id'],
            'title' => $title,
            'start' => $start,
            'end' => $end,
            'allDay' => $allDay,
            'backgroundColor' => $color,
            'borderColor' => $border,
            'textColor' => $this->contrastColor($color),
            'extendedProps' => [
                'source' => 'google',
                'google_id' => $gEvent['id'],
                'calendar_name' => $calName,
                'calendar_color' => $color,
                'description' => $gEvent['description'] ?? null,
                'location' => $gEvent['location'] ?? null,
            ],
        ];
    }

    /**
     * Résout la couleur finale d'un événement Google.
     * colorId Google → hex (palette officielle Google Calendar)
     * https://developers.google.com/calendar/api/v3/reference/colors
     */
    private function resolveGoogleColor(?string $colorId, ?string $calendarColor): string
    {
        // Palette officielle Google Calendar (colorId 1-11)
        $googlePalette = [
            '1' => '#a4bdfc',  // Lavande
            '2' => '#7ae7bf',  // Sauge
            '3' => '#dbadff',  // Raisin
            '4' => '#ff887c',  // Flamant
            '5' => '#fbd75b',  // Banane
            '6' => '#ffb878',  // Mandarine
            '7' => '#46d6db',  // Paon
            '8' => '#e1e1e1',  // Graphite
            '9' => '#5484ed',  // Myrtille
            '10' => '#51b749',  // Basilic
            '11' => '#dc2127',  // Tomate
        ];

        if ($colorId && isset($googlePalette[$colorId])) {
            return $googlePalette[$colorId];
        }

        // Sinon : couleur du calendrier source
        if ($calendarColor) {
            return $calendarColor;
        }

        return '#6b7280'; // Gris défaut
    }

    /**
     * Assombrit une couleur hex de $amount (0-255)
     */
    private function darkenHex(string $hex, int $amount = 20): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = max(0, hexdec(substr($hex, 0, 2)) - $amount);
        $g = max(0, hexdec(substr($hex, 2, 2)) - $amount);
        $b = max(0, hexdec(substr($hex, 4, 2)) - $amount);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Retourne blanc ou noir selon la luminosité du fond
     */
    private function contrastColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        // Formule de luminance relative
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.55 ? '#1f2937' : '#ffffff';
    }

    private function getTypeColor(?RendezVousType $type): string
    {
        return match ($type) {
            RendezVousType::Appel => '#0ea5e9',
            RendezVousType::Permanence => '#10b981',
            RendezVousType::Presentation => '#6366f1',
            RendezVousType::Intervention => '#f97316',
            default => '#64748b',
        };
    }

    public function onEventClick(array $event): void
    {
        $props = $event['extendedProps'] ?? [];
        $source = $props['source'] ?? $event['source'] ?? '';

        if ($source === 'crm') {
            $rdvId = $props['rdv_id'] ?? $event['rdv_id'] ?? null;
            if ($rdvId) {
                $this->redirect('/ns-conseil/rendez-vous/' . $rdvId);
            }
            return;
        }

        if ($source === 'google') {
            $this->dispatch('show-google-event', eventData: [
                'title' => $event['title'] ?? 'Sans titre',
                'start' => $event['start'] ?? null,
                'end' => $event['end'] ?? null,
                'allDay' => $event['allDay'] ?? false,
                'calendar_name' => $props['calendar_name'] ?? $event['calendar_name'] ?? null,
                'calendar_color' => $props['calendar_color'] ?? $event['calendar_color'] ?? '#6b7280',
                'description' => $props['description'] ?? $event['description'] ?? null,
                'location' => $props['location'] ?? $event['location'] ?? null,
                'google_id' => $props['google_id'] ?? $event['google_id'] ?? null,
            ]);
        }
    }
    public function closeEventModal(): void
    {
        $this->showEventModal = false;
        $this->selectedEvent = [];
    }

    /**
     * Clic sur un créneau vide → redirige vers la création d'un RDV
     */
    public function onDateSelect(string $start, ?string $end, bool $allDay, ?array $view, ?array $resource): void
    {
        $this->redirect('/ns-conseil/rendez-vous/create?date=' . urlencode($start));
    }
}
