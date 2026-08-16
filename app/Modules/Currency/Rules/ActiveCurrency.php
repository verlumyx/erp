<?php

declare(strict_types=1);

namespace App\Modules\Currency\Rules;

use App\Modules\Currency\Repositories\Contracts\CurrencyRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * El valor debe ser el código de una moneda activa del catálogo global.
 *
 * Es la única regla de validación de moneda del sistema: ningún módulo declara
 * su propia lista de códigos.
 */
class ActiveCurrency implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('La moneda es obligatoria.');

            return;
        }

        $codes = app(CurrencyRepositoryInterface::class)->activeCodes();

        if (! in_array(strtoupper($value), $codes, true)) {
            $fail('La moneda seleccionada no es válida.');
        }
    }
}
