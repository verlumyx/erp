<?php

declare(strict_types=1);

namespace App\Modules\Store\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Regla de negocio, no recurso ausente: viaja como 422 igual que el resto
 * de las validaciones, tanto en JSON como en Inertia.
 */
class StoreOrderNotPendingException extends ValidationException
{
    public static function forOrder(string $code): self
    {
        return self::withMessages([
            'status' => "El pedido web {$code} ya fue procesado: solo un pedido pendiente se convierte o se rechaza.",
        ]);
    }
}
