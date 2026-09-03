<?php

declare(strict_types=1);

namespace App\Modules\Store\Exceptions;

use Illuminate\Validation\ValidationException;

class ClientAlreadyLinkedException extends ValidationException
{
    public static function forClient(string $name): self
    {
        return self::withMessages([
            'client_id' => "El cliente {$name} ya tiene un comprador vinculado.",
        ]);
    }
}
