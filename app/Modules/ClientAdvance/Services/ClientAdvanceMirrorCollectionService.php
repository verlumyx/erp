<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Services;

use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientCollection\Commands\CreateClientCollectionCommand;
use App\Modules\ClientCollection\Commands\SearchClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateStatusClientCollectionCommand;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * El cobro espejo del anticipo: el documento que sí mueve el dinero.
 *
 * Aprobar un anticipo no lo recibe, lo compromete. Lo que entra es el cobro
 * `COB` que nace aquí, en borrador y sin aplicaciones a facturas, con los
 * importes copiados del anticipo. Confirmarlo o anularlo es lo que mueve el
 * anticipo (`ClientAdvanceCollectionSyncService`).
 */
class ClientAdvanceMirrorCollectionService
{
    public function __construct(
        private readonly ClientCollectionRepositoryInterface $collections,
        private readonly ConfigurationFindService $configurations,
    ) {}

    /**
     * Crea el cobro que recibe el anticipo. Monto, moneda, tasa, cliente y
     * forma de cobro se copian: son propiedad del anticipo y allí se corrigen.
     *
     * @throws ValidationException si el anticipo ya tiene un cobro vivo.
     */
    public function create(ClientAdvance $advance): ClientCollection
    {
        if ($this->liveCollection($advance) instanceof ClientCollection) {
            throw ValidationException::withMessages([
                'status' => 'El anticipo ya tiene un cobro asociado sin anular.',
            ]);
        }

        $id = (string) Str::uuid7();

        $this->collections->create(
            new CreateClientCollectionCommand(
                id: $id,
                companyId: $advance->company_id,
                clientId: $advance->client_id,
                collectionDate: $advance->advance_date->toDateString(),
                createdBy: (string) $advance->created_by,
                /** Nace sin reparto: el anticipo no cancela ninguna factura todavía. */
                applications: [],
                originType: ClientCollection::ORIGIN_ADVANCE,
                originId: $advance->id,
                paymentMethod: $advance->payment_method,
                reference: $advance->reference,
                bankAccount: $advance->bank_account,
                currency: $advance->currency,
                amount: (float) $advance->amount,
                notes: $advance->notes,
            ),
            $this->frozenRates($advance),
        );

        return $this->collections->findOrFail($id, $advance->company_id);
    }

    /**
     * Anula el cobro vivo del anticipo, si lo hay. Lo llama el anticipo cuando
     * se anula estando comprometido: el dinero que había quedado apalabrado
     * deja de estarlo.
     */
    public function cancel(ClientAdvance $advance): void
    {
        $collection = $this->liveCollection($advance);

        if (! $collection instanceof ClientCollection) {
            return;
        }

        $this->collections->updateStatus($collection, new UpdateStatusClientCollectionCommand(
            status: 'cancelled',
            cancellationReason: "El anticipo {$advance->code} fue anulado.",
        ));
    }

    /**
     * El cobro del anticipo que todavía cuenta. Un anticipo no puede tener más
     * de uno en estado distinto de `cancelled`.
     */
    public function liveCollection(ClientAdvance $advance): ?ClientCollection
    {
        $result = $this->collections->search(new SearchClientCollectionCommand(
            filters: [
                'origin_type' => ClientCollection::ORIGIN_ADVANCE,
                'origin_id' => $advance->id,
            ],
            limit: 50,
            companyId: $advance->company_id,
        ));

        foreach ($result['data'] as $collection) {
            if ($collection->status !== 'cancelled') {
                return $collection;
            }
        }

        return null;
    }

    /**
     * Las cuatro columnas de moneda que el anticipo ya congeló. No se vuelven a
     * resolver: el cobro vale lo que valía el anticipo el día que se capturó.
     */
    private function frozenRates(ClientAdvance $advance): DocumentRatesData
    {
        $configuration = $this->configurations->execute($advance->company_id);

        return new DocumentRatesData(
            currency: $advance->currency,
            exchangeRate: (float) $advance->exchange_rate,
            baseCurrency: $advance->base_currency ?? $configuration->base_currency,
            baseExchangeRate: (float) ($advance->base_exchange_rate ?? $advance->exchange_rate),
            rateType: $configuration->rate_type,
            priceDecimals: $configuration->price_decimals,
            amountDecimals: $configuration->amount_decimals,
        );
    }
}
