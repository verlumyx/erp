<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Requests\Concerns;

use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Models\SupplierAddress;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: los campos propios del proveedor más
 * sus dos tablas de detalle (contactos y direcciones).
 *
 * `current_balance` y `advance_balance` no se validan porque no se capturan:
 * son derivados y solo los mueven los documentos del ciclo de compra.
 */
trait ValidatesSupplierPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function supplierRules(): array
    {
        $companyId = session('current_company_id');

        return [
            'name' => ['required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'document_type' => ['required', 'string', Rule::in(Supplier::DOCUMENT_TYPES)],
            'supplier_type_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_supplier_types', 'id')->where('company_id', $companyId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'payment_term_days' => ['nullable', 'integer', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],

            'contacts' => ['nullable', 'array'],
            'contacts.*.id' => ['nullable', 'uuid'],
            'contacts.*.name' => ['required', 'string', 'max:150'],
            'contacts.*.position' => ['nullable', 'string', 'max:100'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'contacts.*.is_primary' => ['required', 'string', 'in:yes,no'],
            'contacts.*.status' => ['nullable', 'string', 'in:active,inactive'],

            'addresses' => ['nullable', 'array'],
            'addresses.*.id' => ['nullable', 'uuid'],
            'addresses.*.type' => ['required', 'string', Rule::in(SupplierAddress::TYPES)],
            'addresses.*.address' => ['required', 'string', 'max:500'],
            'addresses.*.city' => ['nullable', 'string', 'max:100'],
            'addresses.*.state' => ['nullable', 'string', 'max:100'],
            'addresses.*.country' => ['nullable', 'string', 'max:100'],
            'addresses.*.is_default' => ['required', 'string', 'in:yes,no'],
            'addresses.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function supplierMessages(): array
    {
        return [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'document_type.required' => 'La letra del RIF es obligatoria.',
            'document_type.in' => 'La letra del RIF debe ser V, E, J, P, G o C.',
            'document_number.required' => 'El número de RIF o cédula es obligatorio.',
            'document_number.regex' => 'El número de RIF debe contener solo dígitos, sin letra ni guiones.',
            'document_number.unique' => 'Ya existe un proveedor con ese RIF en la empresa.',
            'email.unique' => 'Ya existe un proveedor con ese correo en la empresa.',
            'currency.required' => 'La moneda de compra es obligatoria.',
            'currency.size' => 'La moneda debe ser un código ISO 4217 de 3 letras.',
            'contacts.*.name.required' => 'El nombre del contacto es obligatorio.',
            'contacts.*.email.email' => 'El correo del contacto no tiene un formato válido.',
            'addresses.*.address.required' => 'La dirección es obligatoria.',
            'addresses.*.type.required' => 'Selecciona el tipo de dirección.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateSupplierInvariants(Validator $validator): void
    {
        $primaryCount = 0;

        foreach ($this->input('contacts', []) as $contact) {
            if (($contact['is_primary'] ?? 'no') === 'yes') {
                $primaryCount++;
            }
        }

        if ($primaryCount > 1) {
            $validator->errors()->add('contacts', 'Solo un contacto puede ser el contacto principal.');
        }

        /** La dirección sugerida es una por tipo: una de facturación, una de retiro, etc. */
        $defaultsByType = [];

        foreach ($this->input('addresses', []) as $index => $address) {
            if (($address['is_default'] ?? 'no') !== 'yes') {
                continue;
            }

            $type = (string) ($address['type'] ?? '');

            if (isset($defaultsByType[$type])) {
                $validator->errors()->add(
                    "addresses.{$index}.is_default",
                    'Ya hay otra dirección marcada como predeterminada para ese tipo.',
                );
            }

            $defaultsByType[$type] = true;
        }
    }
}
