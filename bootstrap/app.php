<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
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
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // ── Aliases de middleware ───────────────────────────────────
        // Utilisés dans les routes et les panels Filament
        $middleware->alias([
            'auth'             => \Illuminate\Auth\Middleware\Authenticate::class,
            'auth.basic'       => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session'     => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers'    => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can'              => \Illuminate\Auth\Middleware\Authorize::class,
            'guest'            => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'signed'           => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle'         => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified'         => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            // Spatie Permissions
            'role'             => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'       => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'ensure.super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        // ── Redirection après auth ──────────────────────────────────
        // Filament gère ses propres redirections, mais on garde une
        // route de fallback pour les accès directs à /login
        $middleware->redirectGuestsTo(fn () => route('filament.ns-conseil.auth.login'));

        // ── Exclusions CSRF ─────────────────────────────────────────
        // Webhooks Aircall (POST sans token CSRF)
        $middleware->validateCsrfTokens(except: [
            'api/aircall/*',
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
            \Illuminate\Auth\AuthenticationException $e,
            Request $request
        ) {
            if ($request->is('ns-conseil/*')) {
                return redirect()->route('filament.ns-conseil.auth.login');
            }

            if ($request->is('allopro/*')) {
                return redirect()->route('filament.allopro.auth.login');
            }
        });

    })->create();
