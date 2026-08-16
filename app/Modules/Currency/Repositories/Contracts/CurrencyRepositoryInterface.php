<?php

declare(strict_types=1);

namespace App\Modules\Currency\Repositories\Contracts;

use App\Modules\Currency\Models\Currency;
use Illuminate\Support\Collection;

interface CurrencyRepositoryInterface
{
    /**
     * Monedas activas, ordenadas para los selects.
     *
     * @return Collection<int, Currency>
     */
    public function active(): Collection;

    /**
     * Códigos ISO 4217 de las monedas activas. Es lo que valida el backend.
     *
     * @return array<int, string>
     */
    public function activeCodes(): array;

    public function findByCode(string $code): ?Currency;
}
