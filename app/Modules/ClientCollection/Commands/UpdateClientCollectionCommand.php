<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Commands;

use App\Modules\ClientCollection\Requests\UpdateClientCollectionRequest;

/**
 * El origen no viaja aquí: se congela en la cabecera al crear el cobro y no se
 * cambia después.
 */
class UpdateClientCollectionCommand
{
    /**
     * @param  array<int, ClientCollectionApplicationData>  $applications
     */
    public function __construct(
        public readonly string $clientId,
        public readonly string $collectionDate,
        public readonly array $applications = [],
        /** De qué anticipo o nota sale el crédito, cobrando sin dinero. */
        public readonly ?string $creditSourceId = null,
        public readonly string $paymentMethod = 'cash',
        public readonly ?string $reference = null,
        public readonly ?string $bankAccount = null,
        public readonly ?string $collectedBy = null,
        public readonly ?string $routeId = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly float $amount = 0,
        public readonly float $withholdingAmount = 0,
        public readonly ?string $checkNumber = null,
        public readonly ?string $checkDate = null,
        public readonly ?string $checkStatus = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateClientCollectionRequest $request): self
    {
        return new self(
            clientId: $request->string('client_id')->toString(),
            collectionDate: $request->string('collection_date')->toString(),
            applications: ClientCollectionApplicationData::collection($request->input('applications', [])),
            creditSourceId: $request->input('credit_source_id'),
            paymentMethod: $request->string('payment_method', 'cash')->toString(),
            reference: $request->input('reference'),
            bankAccount: $request->input('bank_account'),
            collectedBy: $request->input('collected_by'),
            routeId: $request->input('route_id'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            amount: (float) $request->input('amount', 0),
            withholdingAmount: (float) $request->input('withholding_amount', 0),
            checkNumber: $request->input('check_number'),
            checkDate: $request->input('check_date'),
            checkStatus: $request->input('check_status'),
            notes: $request->input('notes'),
        );
    }
}
