<?php

declare(strict_types=1);

namespace App\Modules\Account\Commands;

use App\Modules\Account\Requests\UpdateAccountRequest;

class UpdateAccountCommand
{
    /**
     * @param  array<int, array{number: int, pin: ?string, status: ?string, notes: ?string}>  $profiles  Líneas a actualizar (PIN/status/notes).
     */
    public function __construct(
        public readonly string $email,
        public readonly float $cost,
        public readonly string $fechaCompra,
        public readonly string $proximaRenovacion,
        public readonly string $status,
        public readonly ?string $notes = null,
        public readonly ?string $password = null,
        public readonly array $profiles = [],
    ) {}

    public static function fromRequest(UpdateAccountRequest $request): self
    {
        return new self(
            email: $request->string('email')->toString(),
            cost: (float) $request->input('cost'),
            fechaCompra: $request->string('purchase_date')->toString(),
            proximaRenovacion: $request->string('next_renewal')->toString(),
            status: $request->string('status')->toString(),
            notes: $request->input('notes'),
            password: $request->filled('password') ? $request->string('password')->toString() : null,
            profiles: self::normalizeProfiles($request->input('profiles', [])),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $profiles
     * @return array<int, array{number: int, pin: ?string, status: ?string, notes: ?string}>
     */
    private static function normalizeProfiles(array $profiles): array
    {
        return array_values(array_map(static fn (array $profile): array => [
            'number' => (int) $profile['number'],
            'pin' => array_key_exists('pin', $profile) ? $profile['pin'] : null,
            'status' => $profile['status'] ?? null,
            'notes' => array_key_exists('notes', $profile) ? $profile['notes'] : null,
        ], $profiles));
    }
}
