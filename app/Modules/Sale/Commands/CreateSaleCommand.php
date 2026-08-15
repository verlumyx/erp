<?php

declare(strict_types=1);

namespace App\Modules\Sale\Commands;

use App\Modules\Sale\Requests\CreateSaleRequest;

class CreateSaleCommand
{
    /**
     * @param  array<int, string>  $profileIds  Profiles a ocupar (1 si capacity=profile, N si full_account).
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $clientId,
        public readonly string $planId,
        public readonly string $agentId,
        public readonly string $startDate,
        public readonly array $profileIds = [],
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateSaleRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            clientId: $request->string('client_id')->toString(),
            planId: $request->string('plan_id')->toString(),
            agentId: (string) $request->user()->id,
            startDate: $request->string('start_date')->toString(),
            profileIds: array_values(array_map('strval', $request->input('profile_ids', []))),
            notes: $request->input('notes'),
        );
    }
}
