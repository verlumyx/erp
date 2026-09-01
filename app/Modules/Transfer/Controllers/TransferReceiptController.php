<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transfer\Commands\RegisterTransferReceiptCommand;
use App\Modules\Transfer\Requests\RegisterTransferReceiptRequest;
use App\Modules\Transfer\Services\TransferReceiptService;
use Illuminate\Http\RedirectResponse;

/**
 * Registra la llegada de la mercancía al destino. Es una acción propia y no un
 * cambio de estado: lo que escribe es el hecho físico de la recepción, y de él
 * salen la entrada en la bodega de destino y el faltante que se quedó en
 * tránsito.
 */
class TransferReceiptController extends Controller
{
    public function __construct(
        private readonly TransferReceiptService $receiptService,
    ) {}

    public function __invoke(
        RegisterTransferReceiptRequest $request,
        string $company,
        string $id,
    ): RedirectResponse {
        $this->receiptService->execute(
            $id,
            RegisterTransferReceiptCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('transfers.show', ['company' => $company, 'id' => $id]);
    }
}
