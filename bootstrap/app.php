<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    // مسیرهای وب (برای viewها)
        web: __DIR__ . '/../routes/web.php',

        // مسیرهای API
        api: __DIR__ . '/../routes/api.php',

        // دستورات کنسول
        commands: __DIR__ . '/../routes/console.php',

        // مسیر سلامت سرور
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ Middlewareهای سراسری (جهانی)
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        ]);

        // ✅ گروه web (برای صفحات Blade و session)
        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class, // اگر فایلش وجود داره
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // ✅ گروه api (برای درخواست‌های JSON و Sanctum)
        $middleware->group('api', [
            //\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, جهت جلوگیری از ارور CRTF
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
