<?php

declare(strict_types=1);

namespace App\Modules\Account\Commands;

use App\Modules\Account\Requests\CreateAccountRequest;

class CreateAccountCommand
{
    /**
     * @param  array<int, array{number: int, pin: ?string}>  $profiles  PINs precargados al crear (opcional).
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $serviceId,
        public readonly string $email,
        public readonly string $password,
        public readonly float $cost,
        public readonly string $fechaCompra,
        public readonly string $proximaRenovacion,
        public readonly string $status = 'active',
        public readonly ?string $notes = null,
        public readonly array $profiles = [],
        public readonly ?string $createdBy = null,
    ) {}

    public static function fromRequest(CreateAccountRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            serviceId: $request->string('service_id')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            cost: (float) $request->input('cost'),
            fechaCompra: $request->string('purchase_date')->toString(),
            proximaRenovacion: $request->string('next_renewal')->toString(),
            status: $request->input('status', 'active'),
            notes: $request->input('notes'),
            profiles: self::normalizeProfiles($request->input('profiles', [])),
            createdBy: $request->user()?->id,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $profiles
     * @return array<int, array{number: int, pin: ?string}>
     */
    private static function normalizeProfiles(array $profiles): array
    {
        return array_values(array_map(static fn (array $profile): array => [
            'number' => (int) $profile['number'],
            'pin' => $profile['pin'] ?? null,
        ], $profiles));
    }
}
