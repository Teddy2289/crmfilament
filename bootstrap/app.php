<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTokenIsNotExpired;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\RingoverRateLimit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\RateLimiter as RateLimiterService;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\EventServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // ── Confiance aux proxies (Nginx, Load Balancer) ────────────
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO
        );

        // ── Middlewares web globaux ─────────────────────────────────
        // (déjà inclus par défaut dans Laravel 11, mais explicités ici)
        $middleware->web(append: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ]);

        // ── Middlewares API ─────────────────────────────────────────
        // ForceJsonResponse : garantit Accept: application/json sur toutes
        // les requêtes /api/* (réponses JSON même pour 401/403/419…)
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        // ── Aliases de middleware ───────────────────────────────────
        // Utilisés dans les routes et les panels Filament
        $middleware->alias([
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'auth.session' => AuthenticateSession::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,

            // Spatie Permissions
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'ensure.super.admin' => EnsureSuperAdmin::class,

            // Ringover Rate Limiting
            'ringover.rate_limit' => RingoverRateLimit::class,

            // Token expiry enforcement (placed after auth:sanctum in route groups)
            'ensure.token.not.expired' => EnsureTokenIsNotExpired::class,
        ]);

        // ── Rate Limiters API ───────────────────────────────────────
        // Les valeurs sont lues depuis config/api.php (env API_RATE_LIMIT,
        // API_LOGIN_RATE_LIMIT) afin de pouvoir les surcharger par environnement.

        app()->afterResolving(HttpKernel::class, function () {
            $rateLimiter = app()->make(RateLimiterService::class);

            $rateLimiter->for('api', function (Request $request) {
                $limit = (int) config('api.rate_limiting.api_limit', 1000);

                return Limit::perHour($limit)
                    ->by($request->user()?->id ?: $request->ip())
                    ->response(fn () => response()->json([
                        'message' => 'Too Many Requests.',
                    ], 429)->header('Retry-After', 3600));
            });

            $rateLimiter->for('login', function (Request $request) {
                $limit = (int) config('api.rate_limiting.login_limit', 10);

                return Limit::perMinute($limit)
                    ->by($request->ip())
                    ->response(fn () => response()->json([
                        'message' => 'Too Many Attempts.',
                    ], 429)->header('Retry-After', 60));
            });
        });

        // ── Redirection après auth ──────────────────────────────────
        // Filament gère ses propres redirections, mais on garde une
        // route de fallback pour les accès directs à /login
        $middleware->redirectGuestsTo(fn () => route('filament.ns-conseil.auth.login'));

        // ── Exclusions CSRF ─────────────────────────────────────────
        // Webhooks Ringover (POST sans token CSRF)
        $middleware->validateCsrfTokens(except: [
            'api/ringover/*',
            'api/webhooks/*',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // JSON pour toutes les routes API
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
        );

        // Redirection vers le bon panel selon l'URL en cas de 401
        $exceptions->renderable(function (
            AuthenticationException $e,
            Request $request
        ) {
            if ($request->is('ns-conseil/*')) {
                return redirect()->route('filament.ns-conseil.auth.login');
            }

            if ($request->is('allopro/*')) {
                return redirect()->route('filament.allopro.auth.login');
            }
        });

        // ── Handler d'exceptions JSON pour toutes les routes /api/* ─
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null; // Laisser les autres handlers prendre la main
            }

            return match (true) {
                $e instanceof AuthenticationException =>
                    response()->json(['message' => 'Unauthenticated.'], 401),

                $e instanceof AuthorizationException =>
                    response()->json(['message' => 'This action is unauthorized.'], 403),

                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException =>
                    response()->json(['message' => 'Resource not found.'], 404),

                $e instanceof ValidationException =>
                    response()->json([
                        'message' => 'The given data was invalid.',
                        'errors'  => $e->errors(),
                    ], 422),

                $e instanceof TooManyRequestsHttpException =>
                    response()->json(['message' => 'Too many requests.'], 429)
                        ->header('Retry-After', $e->getHeaders()['Retry-After'] ?? 60),

                $e instanceof HttpException =>
                    response()->json(['message' => $e->getMessage() ?: 'HTTP error.'], $e->getStatusCode()),

                default => (function () use ($e) {
                    $errorId = (string) Str::uuid();
                    \Illuminate\Support\Facades\Log::error('API unhandled exception', [
                        'error_id'  => $errorId,
                        'exception' => $e->getMessage(),
                        'trace'     => $e->getTraceAsString(),
                    ]);

                    return response()->json([
                        'message'  => 'Server error.',
                        'error_id' => $errorId,
                    ], 500);
                })(),
            };
        });

        // ── Masquage des écrans d'erreur (routes non-API) ─────────────────
        // APP_DEBUG est lu depuis la BDD (EnvSetting) en priorité, puis
        // depuis la config (valeur .env) en fallback.
        // Permet à un admin de basculer le mode debug depuis l'UI
        // sans redéployer ni modifier manuellement le fichier .env.
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Les requêtes API ont leur propre rendu JSON — ne pas interférer
            if ($request->is('api/*')) {
                return null;
            }

            // Lire la valeur depuis la BDD si la connexion est disponible
            $debugEnabled = false;
            try {
                $setting = \App\Models\EnvSetting::firstWhere('key', 'APP_DEBUG');
                if ($setting !== null) {
                    $debugEnabled = in_array(strtolower($setting->value), ['true', '1'], true);
                } else {
                    $debugEnabled = filter_var(config('app.debug'), FILTER_VALIDATE_BOOLEAN);
                }
            } catch (\Throwable) {
                // Connexion BDD non disponible (boot, migration, etc.) → fallback .env
                $debugEnabled = filter_var(config('app.debug'), FILTER_VALIDATE_BOOLEAN);
            }

            if ($debugEnabled) {
                // Mode debug : laisser Laravel/Ignition afficher l'erreur normalement
                return null;
            }

            // Mode production : page d'erreur générique, sans stack trace
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            // Utiliser une vue dédiée si elle existe (404, 403, 503…)
            $viewName = "errors.{$status}";
            if (view()->exists($viewName)) {
                return response()->view($viewName, [], $status);
            }

            return response()->view('errors.generic', [
                'status'  => $status,
                'message' => $status === 404
                    ? 'La page demandée est introuvable.'
                    : 'Une erreur est survenue. Veuillez réessayer ou contacter l\'administrateur.',
            ], $status);
        });

    })->create();
