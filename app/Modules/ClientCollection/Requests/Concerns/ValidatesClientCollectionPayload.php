<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Requests\Concerns;

use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Models\ClientCollectionApplication;
use App\Modules\Currency\Rules\ActiveCurrency;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera del cobro más su reparto
 * entre facturas.
 *
 * Que la factura sea del mismo cliente y siga teniendo saldo no se comprueba
 * aquí sino en `ClientCollectionOriginService`: es la misma comprobación que
 * hay que repetir al confirmar el cobro, cuando ya no hay request.
 */
trait ValidatesClientCollectionPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function clientCollectionRules(): array
    {
        $companyId = session('current_company_id');

        return [
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('app_clients', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'collection_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', Rule::in(ClientCollection::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:60'],
            'bank_account' => ['nullable', 'string', 'max:60'],
            'collected_by' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            /** Sin foreign key todavía: el módulo de Rutas aún no existe. */
            'route_id' => ['nullable', 'uuid'],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'withholding_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            /** Solo tienen sentido cobrando con cheque; con otra vía se ignoran. */
            'check_number' => ['nullable', 'string', 'max:30'],
            'check_date' => ['nullable', 'date'],
            'check_status' => ['nullable', 'string', Rule::in(ClientCollection::CHECK_STATUSES)],

            /** Un cobro puede nacer sin reparto: el excedente se vuelve anticipo. */
            'applications' => ['nullable', 'array'],
            'applications.*.sales_invoice_id' => [
                'required',
                'uuid',
                Rule::exists('app_sales_invoices', 'id')->where('company_id', $companyId),
            ],
            'applications.*.applied_amount' => ['required', 'numeric', 'gt:0'],
            'applications.*.status' => ['nullable', 'string', Rule::in(ClientCollectionApplication::STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function clientCollectionMessages(): array
    {
        return [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no está disponible.',
            'collection_date.required' => 'La fecha del cobro es obligatoria.',
            'payment_method.in' => 'La forma de cobro indicada no existe.',
            'collected_by.exists' => 'El cobrador indicado no existe.',
            'currency.required' => 'La moneda del cobro es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'amount.required' => 'El monto del cobro es obligatorio.',
            'amount.gt' => 'El monto del cobro debe ser mayor que cero.',
            'check_status.in' => 'El estado del cheque indicado no existe.',
            'applications.*.sales_invoice_id.required' => 'Selecciona la factura que abona esta fila.',
            'applications.*.sales_invoice_id.exists' => 'La factura indicada no existe en esta empresa.',
            'applications.*.applied_amount.gt' => 'El monto aplicado debe ser mayor que cero.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateClientCollectionInvariants(Validator $validator): void
    {
        $rows = $this->activeApplicationRows();

        $this->validateNoRepeatedInvoice($validator, $rows);
        $this->validateAppliedFitsInCollection($validator, $rows);
        $this->validateCheckHasItsNumber($validator);
    }

    /**
     * Una factura aparece una sola vez: la tabla es única por
     * `(factura, origen)`, así que dos filas de la misma factura no cabrían.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function validateNoRepeatedInvoice(Validator $validator, array $rows): void
    {
        $seen = [];

        foreach ($rows as $index => $row) {
            $invoiceId = (string) ($row['sales_invoice_id'] ?? '');

            if ($invoiceId === '') {
                continue;
            }

            if (isset($seen[$invoiceId])) {
                $validator->errors()->add(
                    "applications.{$index}.sales_invoice_id",
                    'Esa factura ya está en el reparto: súmale el monto a su fila.',
                );
            }

            $seen[$invoiceId] = true;
        }
    }

    /**
     * El reparto no puede pasarse de lo que el cobro sabe cancelar.
     *
     * La retención cuenta: es deuda que se salda aunque no entre en caja,
     * porque el cliente la entera al fisco en vez de pagárnosla. Sin retención
     * —el caso normal— el tope es el monto del cobro.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function validateAppliedFitsInCollection(Validator $validator, array $rows): void
    {
        $applied = round(array_sum(array_map(
            static fn (array $row): float => (float) ($row['applied_amount'] ?? 0),
            $rows,
        )), 2);

        $capacity = round(
            (float) $this->input('amount', 0) + (float) $this->input('withholding_amount', 0),
            2,
        );

        if ($applied > $capacity) {
            $validator->errors()->add(
                'applications',
                'El reparto entre facturas supera el monto del cobro.',
            );
        }
    }

    /**
     * Un cheque sin número no se puede seguir hasta el banco, y es lo único que
     * lo identifica cuando rebota.
     */
    private function validateCheckHasItsNumber(Validator $validator): void
    {
        if ($this->input('payment_method') !== 'check') {
            return;
        }

        if (trim((string) $this->input('check_number')) === '') {
            $validator->errors()->add('check_number', 'El número del cheque es obligatorio.');
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activeApplicationRows(): array
    {
        /** @var array<int, array<string, mixed>> $applications */
        $applications = $this->input('applications', []);

        return array_filter(
            $applications,
            static fn (array $row): bool => ($row['status'] ?? 'active') === 'active',
        );
    }
}
