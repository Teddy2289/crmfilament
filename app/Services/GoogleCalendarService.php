<?php

namespace App\Services;

use App\Enums\RendezVousType;
use App\Models\GoogleToken;
use App\Models\RendezVous;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\Google;

class GoogleCalendarService
{
    private Google $provider;

    public function __construct()
    {
        $this->provider = new Google([
            'clientId' => config('services.google.client_id'),
            'clientSecret' => config('services.google.client_secret'),
            'redirectUri' => config('services.google.redirect'),
            'timeout' => 8,
        ]);
    }

    // ── OAuth ────────────────────────────────────────────────────────

    public function getAuthorizationUrl(): string
    {
        return $this->provider->getAuthorizationUrl([
            'scope' => [
                'https://www.googleapis.com/auth/calendar.events',
                'https://www.googleapis.com/auth/calendar.readonly',
                'email',
                'profile',
            ],
            'access_type' => 'offline',
            'prompt' => 'consent',   // force refresh_token
        ]);
    }

    public function exchangeCode(string $code, User $user): GoogleToken
{
    $accessToken = $this->provider->getAccessToken('authorization_code', [
        'code' => $code,
    ]);

    $token = GoogleToken::updateOrCreate(
        ['user_id' => $user->id],
        [
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $accessToken->getRefreshToken(),
            'token_type' => 'Bearer',
            'expires_at' => $accessToken->getExpires()
                ? Carbon::createFromTimestamp($accessToken->getExpires())
                : null,
        ]
    );

    // ⚠️ Le calendrier peut avoir été mis en cache vide AVANT la connexion
    // (getValidToken() échoue silencieusement → fetchSequential(null,...) retourne []
    // → ce [] est caché 30 min par Cache::remember dans getEvents()).
    // On invalide systématiquement après une (re)connexion réussie.
    $this->clearEventsCache($user);
    Cache::forget("gcal_calendars_{$user->id}");

    return $token;
}

    public function revokeToken(User $user): void
    {
        $token = GoogleToken::where('user_id', $user->id)->first();
        if (! $token) {
            return;
        }

        try {
            $this->makeRequest('GET', 'https://oauth2.googleapis.com/revoke', $token, [
                'query' => ['token' => $token->access_token],
            ]);
        } catch (\Throwable) {
        }

        $token->delete();
    }

    // ── Token management ─────────────────────────────────────────────

    private function getValidToken(User $user): GoogleToken
    {
        $token = GoogleToken::where('user_id', $user->id)->first();

        if (! $token) {
            throw new \RuntimeException("L'utilisateur {$user->id} n'a pas connecté Google Calendar.");
        }

        if ($token->isExpired() && $token->refresh_token) {
            $newToken = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $token->refresh_token,
            ]);

            $token->update([
                'access_token' => $newToken->getToken(),
                'expires_at' => $newToken->getExpires()
                    ? Carbon::createFromTimestamp($newToken->getExpires())
                    : null,
            ]);
        }

        return $token->fresh();
    }

    public function isConnected(User $user): bool
    {
        return GoogleToken::where('user_id', $user->id)->exists();
    }

    /**
     * Retourne tous les calendriers Google accessibles au compte connecté.
     * La pagination et le cache sont centralisés dans fetchCalendarMetadata().
     */
    public function getCalendars(User $user): array
    {
        try {
            $token = $this->getValidToken($user);

            return Cache::remember(
                "gcal_calendars_{$user->id}",
                now()->addMinutes(10),
                fn (): array => $this->fetchCalendarMetadata($token)
            );
        } catch (\Throwable $e) {
            if (str_contains(strtolower($e->getMessage()), "invalid_grant")) {
                GoogleToken::where("user_id", $user->id)->delete();
                $this->clearEventsCache($user);
                Cache::forget("gcal_calendars_{$user->id}");
                Log::warning("GoogleCalendar: invalid authorization removed; reconnect required", ["user_id" => $user->id]);
            }
            Log::warning("GoogleCalendar: calendar list unavailable", [
                "user_id" => $user->id,
                "error" => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ── Events CRUD ───────────────────────────────────────────────────

    /**
     * Crée un événement Google Calendar depuis un RendezVous
     */
    public function createEvent(RendezVous $rdv): ?string
    {
        $user = $rdv->commercial ?? $rdv->teleprospecteur;
        if (! $user || ! $this->isConnected($user)) {
            return null;
        }

        try {
            $token = $this->getValidToken($user);
            $calendarId = $rdv->calendar_id ?: ($token->calendar_id ?? 'primary');
            $body = $this->buildEventBody($rdv);

            $response = $this->makeRequest(
                'POST',
                "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events",
                $token,
                ['json' => $body]
            );

            $eventId = $response['id'] ?? null;

            if ($eventId) {
                $rdv->update(['google_event_id' => $eventId]);
            }

            return $eventId;
        } catch (\Throwable $e) {
            Log::error('GoogleCalendar::createEvent failed', [
                'rdv_id' => $rdv->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Met à jour un événement existant
     */
    public function updateEvent(RendezVous $rdv): bool
    {
        if (! $rdv->google_event_id) {
            return false;
        }

        $user = $rdv->commercial ?? $rdv->teleprospecteur;
        if (! $user || ! $this->isConnected($user)) {
            return false;
        }

        try {
            $token = $this->getValidToken($user);
            $calendarId = $rdv->calendar_id ?: ($token->calendar_id ?? 'primary');
            $body = $this->buildEventBody($rdv);

            $this->makeRequest(
                'PUT',
                "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events/{$rdv->google_event_id}",
                $token,
                ['json' => $body]
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('GoogleCalendar::updateEvent failed', [
                'rdv_id' => $rdv->id,
                'event_id' => $rdv->google_event_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Supprime un événement
     */
    public function deleteEvent(RendezVous $rdv): bool
    {
        if (! $rdv->google_event_id) {
            return false;
        }

        $user = $rdv->commercial ?? $rdv->teleprospecteur;
        if (! $user || ! $this->isConnected($user)) {
            return false;
        }

        try {
            $token = $this->getValidToken($user);
            $calendarId = $rdv->calendar_id ?: ($token->calendar_id ?? 'primary');

            $this->makeRequest(
                'DELETE',
                "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events/{$rdv->google_event_id}",
                $token
            );

            $rdv->update(['google_event_id' => null]);

            return true;
        } catch (\Throwable $e) {
            Log::error('GoogleCalendar::deleteEvent failed', [
                'rdv_id' => $rdv->id,
                'event_id' => $rdv->google_event_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Récupère les événements d'un utilisateur pour une période
     */
    /**
     * Récupère les événements de TOUS les calendriers — avec cache 5 min.
     * La clé de cache inclut user + période pour ne pas mélanger les utilisateurs.
     */
    public function getEvents(User $user, \DateTime $start, \DateTime $end): array
    {
        // Plus de isConnected() ici — getValidToken() lève une exception si non connecté
        $weekKey = (new Carbon($start))->format('Y-W');
        $cacheKey = "gcal_events_{$user->id}_{$weekKey}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user, $start, $end) {
            return $this->fetchAllCalendarEvents($user, $start, $end);
        });
    }

    /**
     * Invalide le cache Google Calendar d'un utilisateur (appeler après sync/création RDV)
     */
    public function clearEventsCache(User $user): void
    {
        // Invalide les 12 prochaines semaines
        for ($i = 0; $i < 12; $i++) {
            $weekKey = now()->addWeeks($i)->format('Y-W');
            $cacheKey = "gcal_events_{$user->id}_{$weekKey}";
            Cache::forget($cacheKey);
        }
    }

    /**
     * Fetch réel — appelé uniquement si cache manquant
     */
    /**
     * Fetch réel — appelé uniquement si cache manquant.
     */
    private function fetchAllCalendarEvents(User $user, \DateTime $start, \DateTime $end): array
    {
        $token = $this->getValidToken($user);
        $timeMin = (new Carbon($start))->toRfc3339String();
        $timeMax = (new Carbon($end))->toRfc3339String();

        // La liste peut évoluer dans Google Workspace : cache court et paginé.
        $calendarMeta = Cache::remember(
            "gcal_calendars_{$user->id}",
            now()->addMinutes(10),
            fn (): array => $this->fetchCalendarMetadata($token)
        );

        if ($calendarMeta === []) {
            return [];
        }

        $allEvents = [];
        $seenIds = [];

        foreach ($calendarMeta as $calendarId => $meta) {
            $pageToken = null;

            try {
                do {
                    $query = [
                        'timeMin' => $timeMin,
                        'timeMax' => $timeMax,
                        'singleEvents' => 'true',
                        'orderBy' => 'startTime',
                        'maxResults' => 250,
                    ];

                    if ($pageToken) {
                        $query['pageToken'] = $pageToken;
                    }

                    $response = $this->makeRequest(
                        'GET',
                        'https://www.googleapis.com/calendar/v3/calendars/'.urlencode($calendarId).'/events',
                        $token,
                        ['query' => $query]
                    );

                    foreach ($response['items'] ?? [] as $event) {
                        $eventId = $event['id'] ?? null;
                        if (! $eventId || isset($seenIds[$eventId])) {
                            continue;
                        }

                        $event['_calendar_id'] = $calendarId;
                        $event['_calendar_name'] = $meta['name'];
                        $event['_calendar_color'] = $meta['color'];
                        $allEvents[] = $event;
                        $seenIds[$eventId] = true;
                    }

                    $pageToken = $response['nextPageToken'] ?? null;
                } while ($pageToken);
            } catch (\Throwable $e) {
                // Un agenda peut être partagé puis retiré sans invalider les autres.
                Log::warning('GoogleCalendar: calendar events fetch failed', [
                    'user_id' => $user->id,
                    'calendar_id' => $calendarId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $allEvents;
    }

    /**
     * Récupère tous les calendriers accessibles au compte Google connecté.
     */
    private function fetchCalendarMetadata(\App\Models\GoogleToken $token): array
    {
        $meta = [];
        $pageToken = null;

        do {
            $query = [
                'maxResults' => 250,
                'showHidden' => 'true',
            ];

            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            $calList = $this->makeRequest(
                'GET',
                'https://www.googleapis.com/calendar/v3/users/me/calendarList',
                $token,
                ['query' => $query]
            );

            foreach ($calList['items'] ?? [] as $cal) {
                if (($cal['deleted'] ?? false) || empty($cal['id'])) {
                    continue;
                }

                $meta[$cal['id']] = [
                    'name' => $cal['summary'] ?? $cal['id'],
                    'color' => $cal['backgroundColor'] ?? '#6b7280',
                    'access_role' => $cal['accessRole'] ?? null,
                ];
            }

            $pageToken = $calList['nextPageToken'] ?? null;
        } while ($pageToken);

        Log::info('GoogleCalendar: calendars loaded', [
            'calendar_count' => count($meta),
        ]);

        return $meta;
    }

    public function getAvailableCalendars(User $user): array
    {
        $token = $this->getValidToken($user);
        if (! $token) return [];
        $meta = Cache::remember("gcal_calendars_{$user->id}", now()->addMinutes(10), fn () => $this->fetchCalendarMetadata($token));
        return collect($meta)->mapWithKeys(fn (array $calendar, string $id) => [$id => $calendar['name'] . (! empty($calendar['access_role']) ? ' — ' . $calendar['access_role'] : '')])->all();
    }

    // ── Event body builder ────────────────────────────────────────────

    private function buildEventBody(RendezVous $rdv): array
    {
        $start = $rdv->date_heure;
        $end = $rdv->date_heure->copy()->addHour();

        $body = [
            'summary' => $this->buildEventTitle($rdv),
            'description' => $this->buildEventDescription($rdv),
            'start' => ['dateTime' => $start->toRfc3339String(), 'timeZone' => config('app.timezone', 'Europe/Paris')],
            'end' => ['dateTime' => $end->toRfc3339String(),   'timeZone' => config('app.timezone', 'Europe/Paris')],
            'colorId' => $this->getGoogleColorId($rdv->type),
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email',  'minutes' => 60],
                    ['method' => 'popup',  'minutes' => 15],
                ],
            ],
        ];

        // Lieu si présent
        if ($rdv->lieu || $rdv->adresse_lieu) {
            $body['location'] = trim(($rdv->lieu ?? '').' '.($rdv->adresse_lieu ?? ''));
        }

        // Inviter l'interlocuteur si email connu
        if ($rdv->interlocuteur_email) {
            $body['attendees'] = [
                ['email' => $rdv->interlocuteur_email, 'displayName' => $rdv->interlocuteur_nom],
            ];
        }

        return $body;
    }

    private function buildEventTitle(RendezVous $rdv): string
    {
        $type = $rdv->type instanceof RendezVousType ? $rdv->type->value : $rdv->type;
        $qui = $rdv->interlocuteur_nom ?? 'Sans interlocuteur';

        // Nom du partenaire/prospect si disponible via relation polymorphique
        $entite = null;
        if ($rdv->rdvable) {
            $entite = $rdv->rdvable->nom ?? $rdv->rdvable->nom_tiers ?? null;
        }

        return $entite
            ? "[{$type}] {$qui} — {$entite}"
            : "[{$type}] {$qui}";
    }

    private function buildEventDescription(RendezVous $rdv): string
    {
        $lines = [];

        if ($rdv->interlocuteur_nom) {
            $lines[] = "👤 Interlocuteur : {$rdv->interlocuteur_nom}";
        }
        if ($rdv->interlocuteur_tel) {
            $lines[] = "📞 Téléphone : {$rdv->interlocuteur_tel}";
        }
        if ($rdv->interlocuteur_email) {
            $lines[] = "✉️ Email : {$rdv->interlocuteur_email}";
        }
        if ($rdv->lieu) {
            $lines[] = "📍 Lieu : {$rdv->lieu}";
        }
        if ($rdv->notes) {
            $lines[] = "\n📝 Notes :\n{$rdv->notes}";
        }

        $lines[] = "\n🔗 CRM NS Conseil — RDV #{$rdv->id}";

        return implode("\n", $lines);
    }

    /**
     * Mapping RendezVousType → colorId Google Calendar
     * https://developers.google.com/calendar/api/v3/reference/colors/get
     */
    private function getGoogleColorId(mixed $type): string
    {
        $typeValue = $type instanceof RendezVousType ? $type->value : (string) $type;

        return match ($typeValue) {
            'Appel' => '7',  // Cyan (Peacock)
            'Permanence' => '2',  // Vert (Sage)
            'Presentation' => '9',  // Bleu (Blueberry)
            'Intervention' => '6',  // Orange (Tangerine)
            default => '1',  // Bleu lavande
        };
    }

    // ── HTTP helper ───────────────────────────────────────────────────

    private function makeRequest(string $method, string $url, GoogleToken $token, array $options = []): array
    {
        $client = new Client(['timeout' => 8, 'connect_timeout' => 5]);
        $options = array_merge($options, [
            'headers' => [
                'Authorization' => "Bearer {$token->access_token}",
                'Accept' => 'application/json',
            ],
        ]);

        try {
            $response = $client->request($method, $url, $options);
            $body = $response->getBody()->getContents();

            return $body ? json_decode($body, true) : [];
        } catch (\Throwable $e) {
            $details = $e->getMessage();
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $details .= ' ' . (string) $e->getResponse()->getBody();
            }

            if (str_contains(strtolower($details), 'invalid_grant')) {
                $user = User::find($token->user_id);
                $token->delete();
                if ($user) {
                    $this->clearEventsCache($user);
                }
                Cache::forget("gcal_calendars_{$token->user_id}");
                Log::warning('GoogleCalendar: API rejected token; reconnect required', ['user_id' => $token->user_id]);
                throw new \RuntimeException('L’autorisation Google Calendar a été révoquée ou est invalide. Veuillez reconnecter votre compte Google.');
            }

            throw $e;
        }
    }
}
