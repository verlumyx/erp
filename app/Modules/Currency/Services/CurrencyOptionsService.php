<?php

declare(strict_types=1);

namespace App\Modules\Currency\Services;

use App\Modules\Currency\Models\Currency;
use App\Modules\Currency\Repositories\Contracts\CurrencyRepositoryInterface;

/**
 * Opciones del select de moneda. Se comparte con Inertia en toda la aplicación
 * (`HandleInertiaRequests`), así que cualquier formulario nuevo con un campo
 * moneda usa esta lista sin tocar su controlador.
 */
class CurrencyOptionsService
{
    public function __construct(
        private readonly CurrencyRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, array{code: string, name: string, symbol: string}>
     */
    public function execute(): array
    {
        return $this->repository->active()
            ->map(fn (Currency $currency): array => [
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
            ])
            ->values()
            ->all();
    }
}
