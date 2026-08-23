<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Requests\Concerns;

use App\Modules\Currency\Rules\ActiveCurrency;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: la cabecera del pago más su reparto
 * entre facturas.
 *
 * Que la factura sea del mismo proveedor y siga teniendo saldo no se comprueba
 * aquí sino en `SupplierPaymentOriginService`: es la misma comprobación que hay
 * que repetir al confirmar el pago, cuando ya no hay request.
 */
trait ValidatesSupplierPaymentPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function supplierPaymentRules(): array
    {
        $companyId = session('current_company_id');

        return [
            'supplier_id' => [
                'required',
                'uuid',
                Rule::exists('app_suppliers', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', Rule::in(SupplierPayment::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:60'],
            'bank_account' => ['nullable', 'string', 'max:60'],
            'currency' => ['required', 'string', new ActiveCurrency],
            /** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'withholding_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            /** Un pago puede nacer sin reparto: el excedente se vuelve anticipo. */
            'applications' => ['nullable', 'array'],
            'applications.*.purchase_invoice_id' => [
                'required',
                'uuid',
                Rule::exists('app_purchase_invoices', 'id')->where('company_id', $companyId),
            ],
            'applications.*.applied_amount' => ['required', 'numeric', 'gt:0'],
            'applications.*.status' => ['nullable', 'string', Rule::in(SupplierPaymentApplication::STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function supplierPaymentMessages(): array
    {
        return [
            'supplier_id.required' => 'El proveedor es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no está disponible.',
            'payment_date.required' => 'La fecha del pago es obligatoria.',
            'payment_method.in' => 'La forma de pago indicada no existe.',
            'currency.required' => 'La moneda del pago es obligatoria.',
            'exchange_rate.gt' => 'La tasa de cambio debe ser mayor que cero.',
            'amount.required' => 'El monto del pago es obligatorio.',
            'amount.gt' => 'El monto del pago debe ser mayor que cero.',
            'applications.*.purchase_invoice_id.required' => 'Selecciona la factura que abona esta fila.',
            'applications.*.purchase_invoice_id.exists' => 'La factura indicada no existe en esta empresa.',
            'applications.*.applied_amount.gt' => 'El monto aplicado debe ser mayor que cero.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateSupplierPaymentInvariants(Validator $validator): void
    {
        $rows = $this->activeApplicationRows();

        $this->validateNoRepeatedInvoice($validator, $rows);
        $this->validateAppliedFitsInPayment($validator, $rows);
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
            $invoiceId = (string) ($row['purchase_invoice_id'] ?? '');

            if ($invoiceId === '') {
                continue;
            }

            if (isset($seen[$invoiceId])) {
                $validator->errors()->add(
                    "applications.{$index}.purchase_invoice_id",
                    'Esa factura ya está en el reparto: súmale el monto a su fila.',
                );
            }

            $seen[$invoiceId] = true;
        }
    }

    /**
     * El reparto no puede pasarse de lo que el pago sabe cancelar.
     *
     * La retención cuenta: es deuda que se salda aunque no salga del banco,
     * porque se entera al fisco en vez de pagarse al proveedor. Sin retención
     * —el caso normal— el tope es el monto del pago.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function validateAppliedFitsInPayment(Validator $validator, array $rows): void
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
                'El reparto entre facturas supera el monto del pago.',
            );
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
