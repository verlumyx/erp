<?php

declare(strict_types=1);

namespace App\Modules\Store\Exceptions;

use Illuminate\Validation\ValidationException;

class StoreCustomerAlreadyLinkedException extends ValidationException
{
    public static function forCustomer(string $code): self
    {
        return self::withMessages([
            'client_id' => "El comprador {$code} ya está vinculado a un cliente.",
        ]);
    }
}
