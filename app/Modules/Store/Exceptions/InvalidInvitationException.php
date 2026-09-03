<?php

declare(strict_types=1);

namespace App\Modules\Store\Exceptions;

use Illuminate\Validation\ValidationException;

class InvalidInvitationException extends ValidationException
{
    public static function create(): self
    {
        return self::withMessages([
            'token' => 'La invitación no es válida o ya venció. Pide una nueva desde tu proveedor.',
        ]);
    }
}
