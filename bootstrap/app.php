<?php

use App\Http\Middleware\ArahkanKePanelSesuaiPeran;
use App\Http\Middleware\HeaderKeamanan;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Di belakang reverse proxy fakultas yang memotong awalan /puspresma (STANDAR-TEKNIS §2.4).
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES'),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );

        // Header keamanan pada semua respons (CSP, nosniff, frame, referrer, HSTS di produksi).
        $middleware->append(HeaderKeamanan::class);

        // Pengalihan panel harus berjalan sebelum Authenticate Filament (yang menjawab 403).
        $middleware->prependToPriorityList(AuthenticatesRequests::class, ArahkanKePanelSesuaiPeran::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
