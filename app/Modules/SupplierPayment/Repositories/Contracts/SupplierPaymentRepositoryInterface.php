<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SupplierPayment\Commands\CreateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\PostSupplierPaymentApplicationCommand;
use App\Modules\SupplierPayment\Commands\SearchSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\UpdateStatusSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\UpdateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\WriteSupplierPaymentApplicationCommand;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;

interface SupplierPaymentRepositoryInterface
{
    public function create(CreateSupplierPaymentCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?SupplierPayment;

    public function findOrFail(string $id, ?string $companyId = null): SupplierPayment;

    public function update(SupplierPayment $model, UpdateSupplierPaymentCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(SupplierPayment $model, UpdateStatusSupplierPaymentCommand $command): void;

    /**
     * El reparto vigente del pago, en el orden en que se capturó.
     *
     * @return array<int, SupplierPaymentApplication>
     */
    public function activeApplications(SupplierPayment $model): array;

    /**
     * Las aplicaciones vivas de un origen cualquiera: un pago, un anticipo o
     * una nota de crédito.
     *
     * @return array<int, SupplierPaymentApplication>
     */
    public function applicationsOf(string $sourceType, string $sourceId): array;

    /** La fila con la que un origen abona una factura, viva o revertida. */
    public function findApplication(
        string $sourceType,
        string $sourceId,
        string $purchaseInvoiceId,
    ): ?SupplierPaymentApplication;

    /**
     * Escribe la fila con la que un anticipo o una nota de crédito abona una
     * factura. Solo la llama `SupplierPaymentApplyCreditService`.
     */
    public function writeApplication(
        WriteSupplierPaymentApplicationCommand $command,
    ): SupplierPaymentApplication;

    /** Deja la aplicación abonada: es lo que hace el pago al confirmarse. */
    public function postApplication(
        SupplierPaymentApplication $application,
        PostSupplierPaymentApplicationCommand $command,
    ): void;

    /** Revertir no borra la fila: la deja en `reversed` y libera el saldo. */
    public function reverseApplication(SupplierPaymentApplication $application): void;

    /** @return array{ data: SupplierPayment[], total: int } */
    public function search(SearchSupplierPaymentCommand $command): array;
}
