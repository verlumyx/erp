<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company.access' => \App\Http\Middleware\EnsureUserBelongsToCompany::class,
            'company.access.api' => \App\Http\Middleware\EnsureUserBelongsToCompanyApi::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Symfony\Component\HttpFoundation\Response $response, Throwable $exception, Request $request) {
            $isForbidden = $exception instanceof AuthorizationException
                || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 403);

            if ($isForbidden) {
                if ($request->header('X-Inertia')) {
                    return back()->with('error', 'No tienes permiso para acceder a esta sección.');
                }

                return Inertia::render('errors/403', [
                    'message' => 'No tienes permiso para acceder a esta sección.',
                ])->toResponse($request)->setStatusCode(403);
            }

            $isModuleNotFound = str_starts_with($exception::class, 'App\\Modules\\')
                && str_ends_with($exception::class, 'NotFoundException');

            if ($isModuleNotFound) {
                if ($request->header('X-Inertia')) {
                    return back()->with('error', 'El recurso solicitado no existe.');
                }

                return response('Not Found', 404);
            }

            return $response;
        });
    })->create();
