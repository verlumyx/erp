<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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

            /**
             * Falta la tasa para valorar el documento. Es un error de captura,
             * no un recurso inexistente: vuelve al formulario con el mensaje
             * que dice qué moneda y qué fecha hay que cargar. Va antes del
             * bloque siguiente, que lo tomaría por un 404 por su nombre.
             */
            if ($exception instanceof ExchangeRateNotFoundException) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $exception->getMessage(),
                        'errors' => ['exchange_rate' => [$exception->getMessage()]],
                    ], 422);
                }

                return back()
                    ->withInput()
                    ->withErrors(['exchange_rate' => $exception->getMessage()]);
            }

            $isModuleNotFound = str_starts_with($exception::class, 'App\\Modules\\')
                && str_ends_with($exception::class, 'NotFoundException');

            if ($isModuleNotFound) {
                if ($request->header('X-Inertia')) {
                    return back()->with('error', 'El recurso solicitado no existe.');
                }

                /*
                 * Una dirección abierta a mano o pegada de un chat no tiene a
                 * dónde volver: en vez de un «Not Found» pelado se dibuja la
                 * pantalla con las salidas, y la más útil es el listado del
                 * módulo del que venía la dirección.
                 *
                 * Se deduce de la propia ruta, que en esta aplicación siempre
                 * es `/{company}/{módulo}/...` con el listado nombrado
                 * `{módulo}.index`. Si no cuadra —una ruta con otra forma— la
                 * pantalla se queda sin ese botón y con los otros dos.
                 */
                $company = $request->segment(1) ?? '';
                $module = $request->segment(2) ?? '';

                $listUrl = Str::isUuid($company) && $module !== '' && Route::has("{$module}.index")
                    ? route("{$module}.index", ['company' => $company])
                    : null;

                return Inertia::render('errors/404', [
                    'message' => 'El registro que buscas no existe o fue movido.',
                    'listUrl' => $listUrl,
                ])->toResponse($request)->setStatusCode(404);
            }

            return $response;
        });
    })->create();
