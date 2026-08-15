<?php

declare(strict_types=1);

namespace App\Modules\Auth\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvalidCredentialsException extends Exception
{
    protected $message = 'Invalid credentials.';

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Las credenciales proporcionadas son incorrectas.',
        ], 401);
    }
}
