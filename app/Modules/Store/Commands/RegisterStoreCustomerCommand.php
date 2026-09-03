<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\Api\RegisterStoreCustomerApiRequest;
use Illuminate\Support\Str;

/**
 * Alta de un comprador. Sirve para el registro desde la tienda (`active`, con
 * contraseña) y para la invitación desde el ERP (`invited`, sin contraseña y
 * ya vinculada al cliente).
 */
class RegisterStoreCustomerCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $documentType,
        public readonly ?string $documentNumber,
        public readonly ?string $passwordHash,
        public readonly string $status = 'active',
        public readonly ?string $clientId = null,
        public readonly ?string $linkSource = null,
        public readonly ?string $linkedBy = null,
        public readonly ?string $invitationTokenHash = null,
        public readonly ?string $invitationExpiresAt = null,
        public readonly ?string $createdBy = null,
    ) {}

    public static function fromRequest(RegisterStoreCustomerApiRequest $request, string $companyId, string $passwordHash): self
    {
        $type = $request->input('document_type');
        $number = $request->input('document_number');

        return new self(
            id: (string) Str::uuid7(),
            companyId: $companyId,
            name: $request->string('name')->toString(),
            email: strtolower(trim($request->string('email')->toString())),
            phone: $request->input('phone'),
            documentType: $type === null || $type === '' ? null : strtoupper((string) $type),
            documentNumber: $number === null || $number === '' ? null : preg_replace('/\D/', '', (string) $number),
            passwordHash: $passwordHash,
        );
    }
}
