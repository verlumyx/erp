<?php

declare(strict_types=1);

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class PermissionHelper
{
    /**
     * Verificar si el usuario tiene el permiso requerido.
     * Si no tiene permiso, retorna una respuesta 403 con Inertia.
     *
     * @return Response|JsonResponse|null Retorna Response si no tiene permiso, null si tiene permiso
     */
    public static function checkPermission(Request $request, string $permission, string $message): Response|JsonResponse|null
    {
        $user = $request->user();

        // Si no hay usuario autenticado, redirigir al login
        if (! $user) {
            return Inertia::location(route('login'));
        }

        // Si el usuario no tiene el permiso, mostrar página de error 403
        if (! $user->hasPermission($permission)) {
            return Inertia::render('errors/403', [
                'message' => $message,
            ])->toResponse($request)->setStatusCode(403);
        }

        // El usuario tiene permiso, continuar
        return null;
    }
}
