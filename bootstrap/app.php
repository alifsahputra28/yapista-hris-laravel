<?php

use App\Http\Middleware\SecurityHeaders;
use App\Support\Auth\UserRedirector;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'role' => App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->redirectUsersTo(
            fn (Request $request): string => app(UserRedirector::class)->pathFor($request->user())
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
