<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Commands\UpdateStatusEntryCommand;
use App\Modules\Entry\Exceptions\EntryNotFoundException;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Services\PurchaseOrderMirrorEntryService;
use Illuminate\Support\Facades\DB;

class EntryUpdateStatusService
{
    public function __construct(
        private readonly EntryRepositoryInterface $repository,
        private readonly EntryPostingService $posting,
        private readonly PurchaseOrderMirrorEntryService $orderMirror,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los costos de la entrada: lo que
     * mueve es el inventario y lo recibido de la orden de compra, y solo en los
     * dos momentos que importan —confirmarla y anularla ya confirmada—.
     */
    public function execute(
        string $id,
        UpdateStatusEntryCommand $command,
        ?string $companyId = null,
    ): Entry {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new EntryNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = in_array($model->status, Entry::POSTED_STATUSES, true);

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
                /**
                 * Una recepción parcial deja saldo en su orden de compra: lo que
                 * no llegó necesita su propio borrador para recibirse después.
                 * `post()` ya apuntó lo recibido, así que aquí el pendiente es
                 * real.
                 */
                if ($model->sourceable instanceof PurchaseOrder) {
                    $this->orderMirror->createRemainder($model->sourceable, $model->id);
                }
            }

            /** Un borrador anulado no revierte nada: nunca llegó a meter mercancía. */
            if ($command->status === 'cancelled' && $wasPosted) {
                $this->posting->reverse($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
