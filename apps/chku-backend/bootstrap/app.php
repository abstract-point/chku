<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Caddy terminates TLS and forwards X-Forwarded-* headers.
        $middleware->trustProxies(at: '*');

        $middleware->api(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'active.member' => \App\Http\Middleware\RequireActiveMember::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('reminders:meetings')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('reminders:reading-progress')->weeklyOn(1, '10:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
