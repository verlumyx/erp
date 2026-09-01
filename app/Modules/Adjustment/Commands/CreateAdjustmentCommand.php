<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Commands;

use App\Modules\Adjustment\Requests\CreateAdjustmentRequest;

class CreateAdjustmentCommand
{
    /**
     * @param  array<int, AdjustmentLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $warehouseId,
        public readonly string $adjustmentDate,
        /** Justificación obligatoria: un ajuste sin motivo no se registra. */
        public readonly string $reason,
        public readonly string $createdBy,
        public readonly array $lines = [],
        public readonly string $type = 'physical_count',
        public readonly string $direction = 'mixed',
        public readonly ?string $countId = null,
        public readonly ?string $attachmentPath = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateAdjustmentRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            warehouseId: $request->string('warehouse_id')->toString(),
            adjustmentDate: $request->string('adjustment_date')->toString(),
            reason: $request->string('reason')->toString(),
            createdBy: $request->user()->id,
            lines: AdjustmentLineData::collection($request->input('lines', [])),
            type: $request->string('type', 'physical_count')->toString(),
            direction: $request->string('direction', 'mixed')->toString(),
            countId: $request->input('count_id'),
            attachmentPath: $request->input('attachment_path'),
            notes: $request->input('notes'),
        );
    }
}
