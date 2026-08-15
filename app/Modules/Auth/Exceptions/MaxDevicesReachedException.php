<?php

declare(strict_types=1);

namespace App\Modules\Auth\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaxDevicesReachedException extends Exception
{
    protected $message = 'Maximum number of devices reached.';

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Has alcanzado el máximo de dispositivos permitidos. Cierra sesión en otro dispositivo para continuar.',
        ], 403);
    }
}
