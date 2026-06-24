<?php

use App\Http\Controllers\Api\GoogleOAuthController;
use App\Http\Controllers\PdfController;
use App\Models\GoogleToken;
use App\Services\AircallService;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/ns-conseil/aircall/recording/{callId}', function (string $callId) {
    $call = app(AircallService::class)->getCall($callId);

    return response()->json(['url' => $call['recording'] ?? null]);
})->middleware(['auth', 'web']);

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/google/redirect', [GoogleOAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/google/callback', [GoogleOAuthController::class, 'callback'])->name('google.callback');
    Route::get('/google/disconnect', [GoogleOAuthController::class, 'disconnect'])->name('google.disconnect');

    Route::get('/pdf/facture/{facture}', [PdfController::class, 'facture'])->name('factures.pdf');
    Route::get('/pdf/devis/{devis}', [PdfController::class, 'devis'])->name('devis.pdf');
});

Route::get('/debug-calendar', function () {
    $user = auth()->user();
    $token = GoogleToken::where('user_id', $user->id)->first();
    $client = new Client;

    $start = Carbon::now()->startOfMonth()->toRfc3339String();
    $end = Carbon::now()->endOfMonth()->toRfc3339String();

    // Lister tous les calendriers
    $calResp = $client->get('https://www.googleapis.com/calendar/v3/users/me/calendarList', [
        'headers' => ['Authorization' => "Bearer {$token->access_token}"],
    ]);
    $calendars = json_decode($calResp->getBody(), true)['items'] ?? [];

    $allEvents = [];

    foreach ($calendars as $cal) {
        $calId = $cal['id'];
        try {
            $evResp = $client->get('https://www.googleapis.com/calendar/v3/calendars/'.urlencode($calId).'/events', [
                'headers' => ['Authorization' => "Bearer {$token->access_token}"],
                'query' => [
                    'timeMin' => $start,
                    'timeMax' => $end,
                    'singleEvents' => 'true',
                    'orderBy' => 'startTime',
                    'maxResults' => 50,
                ],
            ]);
            $events = json_decode($evResp->getBody(), true)['items'] ?? [];

            foreach ($events as $e) {
                $allEvents[] = [
                    'calendar_id' => $calId,
                    'calendar_name' => $cal['summary'] ?? '?',
                    'calendar_color' => $cal['backgroundColor'] ?? null,
                    'event_id' => $e['id'],
                    'summary' => $e['summary'] ?? '(sans titre)',
                    'start' => $e['start']['dateTime'] ?? $e['start']['date'] ?? null,
                    'colorId' => $e['colorId'] ?? null,
                    'event_color' => $e['color'] ?? null,
                    'status' => $e['status'] ?? null,
                ];
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    // Résumé des colorIds utilisés
    $colorIds = array_unique(array_filter(array_column($allEvents, 'colorId')));
    $calColors = array_unique(array_filter(array_column($allEvents, 'calendar_color')));

    return response()->json([
        'total_events' => count($allEvents),
        'color_ids_used' => array_values($colorIds),
        'calendar_colors' => array_values($calColors),
        'events' => $allEvents,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->middleware(['web', 'auth']);
