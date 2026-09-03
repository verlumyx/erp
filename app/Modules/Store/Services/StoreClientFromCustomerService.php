<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Client\Commands\CreateClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Services\ClientCreateService;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreSetting;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Crea el cliente del ERP a partir del comprador, con los defaults de
 * Ajustes de tienda. No pide datos adicionales: nombre, RIF, correo y
 * teléfono vienen del comprador.
 */
class StoreClientFromCustomerService
{
    public function __construct(
        private readonly ClientCreateService $clients,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(StoreSetting $settings, StoreCustomer $customer, string $createdBy): Client
    {
        if ($settings->default_client_type_id === null) {
            throw ValidationException::withMessages([
                'default_client_type_id' => 'Configura el tipo de cliente por defecto en Ajustes de tienda antes de crear clientes desde pedidos web.',
            ]);
        }

        if ($customer->document_type === null || $customer->document_number === null) {
            throw ValidationException::withMessages([
                'document_number' => 'El comprador necesita RIF para crear el cliente.',
            ]);
        }

        return $this->clients->execute(new CreateClientCommand(
            id: (string) Str::uuid7(),
            companyId: (string) $settings->company_id,
            name: $customer->name,
            documentType: $customer->document_type,
            documentNumber: $customer->document_number,
            createdBy: $createdBy,
            clientTypeId: $settings->default_client_type_id,
            priceListId: $settings->price_list_id,
            phone: $customer->phone,
            email: $customer->email,
            paymentTermDays: 0,
            creditLimit: '0',
            salespersonId: $createdBy,
        ));
    }
}
