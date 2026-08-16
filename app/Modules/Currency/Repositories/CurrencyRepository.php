<?php

declare(strict_types=1);

namespace App\Modules\Currency\Repositories;

use App\Modules\Currency\Models\Currency;
use App\Modules\Currency\Repositories\Contracts\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    /**
     * El catálogo es global y de tres o cuatro filas, y se consulta en cada
     * request para compartir el select: se resuelve una sola vez por request.
     *
     * @var Collection<int, Currency>|null
     */
    private ?Collection $cache = null;

    /**
     * @return Collection<int, Currency>
     */
    public function active(): Collection
    {
        return $this->cache ??= Currency::query()
            ->where('status', 'active')
            ->orderBy('order')
            ->orderBy('code')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function activeCodes(): array
    {
        return $this->active()->pluck('code')->all();
    }

    public function findByCode(string $code): ?Currency
    {
        return Currency::query()->where('code', strtoupper($code))->first();
    }
}
