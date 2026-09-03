<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Store\Models\StoreCustomer>
 */
class StoreCustomerFactory extends Factory
{
    protected $model = \App\Modules\Store\Models\StoreCustomer::class;

    private static int $sequence = 0;

    /** Contraseña en claro con la que nacen los compradores de prueba. */
    public const PASSWORD = 'secret-1234';

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = ++self::$sequence;

        return [
            'company_id' => Company::factory(),
            'code' => 'CWE'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'client_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+58 4## ### ####'),
            'document_type' => null,
            'document_number' => null,
            'password_hash' => Hash::make(self::PASSWORD),
            'email_verified_at' => null,
            'last_login_at' => null,
            'linked_at' => null,
            'linked_by' => null,
            'link_source' => null,
            'invitation_token_hash' => null,
            'invitation_expires_at' => null,
            'status' => 'active',
            'created_by' => null,
        ];
    }

    public function withDocument(string $type = 'V', ?string $number = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'document_type' => $type,
            'document_number' => $number ?? str_pad((string) (20000000 + self::$sequence), 8, '0', STR_PAD_LEFT),
        ]);
    }

    /** Creado desde el ERP, sin contraseña todavía. */
    public function invited(string $plainToken = 'invitation-token'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'invited',
            'password_hash' => null,
            'invitation_token_hash' => hash('sha256', $plainToken),
            'invitation_expires_at' => now()->addDays(7),
        ]);
    }

    public function linkedTo(string $clientId, string $source = 'manual'): static
    {
        return $this->state(fn (array $attributes): array => [
            'client_id' => $clientId,
            'linked_at' => now(),
            'link_source' => $source,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
