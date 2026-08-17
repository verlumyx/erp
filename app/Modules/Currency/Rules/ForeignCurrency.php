<?php

declare(strict_types=1);

namespace App\Modules\Currency\Rules;

use App\Modules\Currency\Models\Currency;
use App\Modules\Currency\Repositories\Contracts\CurrencyRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * El valor debe ser el código de una moneda activa distinta de la local.
 *
 * Se usa donde la moneda solo tiene sentido frente al bolívar —una tasa de
 * cambio, por ejemplo—: registrar una tasa del bolívar contra sí mismo no
 * significa nada, siempre vale 1.
 */
class ForeignCurrency implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('La moneda es obligatoria.');

            return;
        }

        $code = strtoupper($value);

        if ($code === Currency::LOCAL_CODE) {
            $fail('El bolívar no lleva tasa de cambio: su valor es siempre 1.');

            return;
        }

        $codes = app(CurrencyRepositoryInterface::class)->activeCodes();

        if (! in_array($code, $codes, true)) {
            $fail('La moneda seleccionada no es válida.');
        }
    }
}
