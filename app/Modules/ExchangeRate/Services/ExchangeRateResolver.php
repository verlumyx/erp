<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Services;

use App\Modules\Currency\Models\Currency;
use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Modules\ExchangeRate\Services\Contracts\ExchangeRateResolverInterface;

class ExchangeRateResolver implements ExchangeRateResolverInterface
{
    /**
     * Un documento consulta la misma moneda varias veces mientras se calcula
     * (cabecera, líneas, impuestos): se resuelve una sola vez por request.
     *
     * @var array<string, float>
     */
    private array $cache = [];

    public function __construct(
        private readonly ExchangeRateRepositoryInterface $repository,
    ) {}

    public function rateFor(string $companyId, string $currency, string $date, string $type = 'legal'): float
    {
        $currency = strtoupper($currency);

        if ($currency === Currency::LOCAL_CODE) {
            return 1.0;
        }

        $key = implode('|', [$companyId, $currency, $date, $type]);

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $rate = $this->repository->findLatestUpTo($companyId, $currency, $date, $type);

        if ($rate === null) {
            throw ExchangeRateNotFoundException::forCurrency($currency, $date);
        }

        return $this->cache[$key] = (float) $rate->rate;
    }

    public function convert(float $amount, string $from, string $to, string $companyId, string $date, string $type = 'legal'): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        $inLocal = $amount * $this->rateFor($companyId, $from, $date, $type);

        return $inLocal / $this->rateFor($companyId, $to, $date, $type);
    }
}
