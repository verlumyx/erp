<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Commands;

use App\Modules\SalesOrder\Requests\UpdateStatusSalesOrderRequest;

class UpdateStatusSalesOrderCommand
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $cancellationReason = null,
        public readonly ?string $approvedBy = null,
        /** Quién confirma puede levantar el bloqueo de crédito del cliente. */
        public readonly bool $allowsCreditOverride = false,
    ) {}

    public static function fromRequest(UpdateStatusSalesOrderRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            cancellationReason: $request->input('cancellation_reason'),
            /** Confirmar es aprobar: queda registrado quién dio el visto bueno. */
            approvedBy: $request->user()?->id,
            allowsCreditOverride: $request->user()?->hasPermission('sales-orders.override-credit-limit') ?? false,
        );
    }
}
