<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Services;

use App\Modules\SupplierAdvance\Commands\UpdateStatusSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Exceptions\SupplierAdvanceNotFoundException;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SupplierAdvanceUpdateStatusService
{
    public function __construct(
        private readonly SupplierAdvanceRepositoryInterface $repository,
        private readonly SupplierAdvanceMirrorPaymentService $mirror,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes del anticipo: lo que
     * mueve es su pago espejo. Aprobarlo lo crea; anular el anticipo lo anula
     * con él, para que no quede un pago vivo de un documento muerto.
     */
    public function execute(
        string $id,
        UpdateStatusSupplierAdvanceCommand $command,
        ?string $companyId = null,
    ): SupplierAdvance {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierAdvanceNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            if ($command->status === 'pending_confirmation') {
                $this->mirror->create($model);
            }

            if ($command->status === 'cancelled') {
                $this->mirror->cancel($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
