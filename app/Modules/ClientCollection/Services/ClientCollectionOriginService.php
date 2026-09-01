<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\ClientCollection\Commands\ClientCollectionApplicationData;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Las comprobaciones del cobro que la base de datos no puede hacer por su
 * cuenta: el origen no es una foreign key —apunta a dos tablas según el tipo—
 * y la factura de cada aplicación tiene que ser del mismo cliente, estar viva y
 * tener saldo.
 *
 * Vive aparte de los servicios de acción porque Crear, Actualizar y Confirmar
 * la necesitan igual: entre que se captura el reparto y se confirma el cobro,
 * otro documento pudo haberse llevado el saldo.
 *
 * @throws ValidationException
 */
class ClientCollectionOriginService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $salesInvoices,
    ) {}

    /**
     * El origen del cobro. `advance` no entra por aquí: ese cobro nace al
     * aprobar un anticipo, no desde esta pantalla.
     */
    public function guardOrigin(string $originType, ?string $originId, string $clientId, ?string $companyId): void
    {
        if (! in_array($originType, ClientCollection::CREATABLE_ORIGIN_TYPES, true)) {
            throw ValidationException::withMessages([
                'origin_type' => 'Un cobro solo se inicia desde un cliente o desde una factura.',
            ]);
        }

        if ($originType === 'client') {
            if (filled($originId)) {
                throw ValidationException::withMessages([
                    'origin_id' => 'Un cobro iniciado desde el cliente no apunta a ningún documento.',
                ]);
            }

            return;
        }

        if (blank($originId)) {
            throw ValidationException::withMessages([
                'origin_id' => 'Falta la factura desde la que se inicia el cobro.',
            ]);
        }

        $this->collectibleInvoice($originId, $clientId, $companyId, 'origin_id');
    }

    /**
     * El reparto entre facturas. Cada una tiene que seguir teniendo saldo por
     * lo que se le quiere abonar: se comprueba al guardar y otra vez al
     * confirmar.
     *
     * @param  array<int, ClientCollectionApplicationData>  $applications
     */
    public function guardApplications(array $applications, string $clientId, ?string $companyId): void
    {
        foreach ($applications as $index => $row) {
            if ($row->status !== 'active') {
                continue;
            }

            $invoice = $this->collectibleInvoice(
                $row->salesInvoiceId,
                $clientId,
                $companyId,
                "applications.{$index}.sales_invoice_id",
            );

            if ($row->appliedAmount > round((float) $invoice->balance, 2)) {
                throw ValidationException::withMessages([
                    "applications.{$index}.applied_amount" => "La factura {$invoice->code} solo debe {$invoice->balance}.",
                ]);
            }
        }
    }

    /**
     * La factura existe en esta empresa, es de este cliente, ya generó su
     * cuenta por cobrar y todavía debe algo.
     */
    private function collectibleInvoice(
        string $invoiceId,
        string $clientId,
        ?string $companyId,
        string $field,
    ): SalesInvoice {
        $invoice = $this->salesInvoices->findById($invoiceId, $companyId);

        if (! $invoice instanceof SalesInvoice) {
            throw ValidationException::withMessages([
                $field => 'La factura indicada no existe en esta empresa.',
            ]);
        }

        if ($invoice->client_id !== $clientId) {
            throw ValidationException::withMessages([
                $field => 'La factura pertenece a otro cliente: un cobro no cruza clientes.',
            ]);
        }

        if (! in_array($invoice->status, SalesInvoice::COLLECTIBLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                $field => "La factura {$invoice->code} no admite cobros en su estado actual.",
            ]);
        }

        if (round((float) $invoice->balance, 2) <= 0) {
            throw ValidationException::withMessages([
                $field => "La factura {$invoice->code} ya está saldada.",
            ]);
        }

        return $invoice;
    }
}
