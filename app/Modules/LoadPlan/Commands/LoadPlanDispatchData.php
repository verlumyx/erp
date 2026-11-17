<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Commands;

class LoadPlanDispatchData
{
    public function __construct(
        public readonly string $dispatchId,
        public readonly int $sequence = 0,
        public readonly ?string $id = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row, int $index = 0): self
    {
        return new self(
            dispatchId: (string) ($row['dispatch_id'] ?? ''),
            sequence: isset($row['sequence']) ? (int) $row['sequence'] : $index + 1,
            id: isset($row['id']) ? (string) $row['id'] : null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }
}
