<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use App\Modules\SalesOrder\Models\SalesOrder;
use Illuminate\Validation\ValidationException;

/**
 * El crédito del cliente decidiendo si el pedido puede confirmarse.
 *
 * Un pedido a crédito compromete dinero que todavía no ha entrado, así que
 * antes de confirmarlo se mira lo que el cliente ya debe: si está bloqueado, o
 * si lo que debe más este pedido se pasa de su límite, el pedido no avanza
 * (`docs/ventas.md` §1, §2.2).
 *
 * El bloqueo no es absoluto: lo levanta una autorización explícita —el permiso
 * `sales-orders.override-credit-limit`—, y quien la ejerce queda sellado en
 * `approved_by` / `approved_at` como cualquier otra confirmación.
 *
 * Una venta de contado (`payment_term_days = 0`) no pasa por aquí: no hay
 * crédito que consumir. Un cliente sin límite (`credit_limit = 0`) tampoco
 * tiene tope que exceder, pero sí puede estar bloqueado.
 */
class SalesOrderCreditService
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    /**
     * @throws ValidationException si el crédito no da y nadie lo autorizó.
     */
    public function guard(SalesOrder $order, bool $allowsOverride = false): void
    {
        if ($allowsOverride) {
            return;
        }

        $client = $this->clients->findById($order->client_id, $order->company_id);

        if (! $client instanceof Client) {
            return;
        }

        if ($client->credit_blocked === 'yes') {
            throw ValidationException::withMessages([
                'status' => "El cliente {$client->name} tiene el crédito bloqueado.",
            ]);
        }

        if ((int) $order->payment_term_days <= 0) {
            return;
        }

        $limit = round((float) $client->credit_limit, 2);

        if ($limit <= 0) {
            return;
        }

        $committed = round((float) $client->current_balance + (float) $order->total, 2);

        if ($committed > $limit) {
            throw ValidationException::withMessages([
                'status' => "El pedido deja al cliente en {$committed} y su límite de crédito es {$limit}.",
            ]);
        }
    }
}
