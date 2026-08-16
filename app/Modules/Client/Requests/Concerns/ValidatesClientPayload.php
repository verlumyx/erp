<?php

declare(strict_types=1);

namespace App\Modules\Client\Requests\Concerns;

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas por Create/Update: los campos propios del cliente más
 * sus dos tablas de detalle (contactos y direcciones).
 *
 * `current_balance` y `advance_balance` no se validan porque no se capturan:
 * son derivados y solo los mueven los documentos del ciclo de venta.
 */
trait ValidatesClientPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function clientRules(): array
    {
        $companyId = session('current_company_id');

        return [
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'document_type' => ['required', 'string', Rule::in(Client::DOCUMENT_TYPES)],
            'client_type_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_client_types', 'id')->where('company_id', $companyId),
            ],
            'price_list_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_price_lists', 'id')->where('company_id', $companyId),
            ],
            'salesperson_id' => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'payment_term_days' => ['nullable', 'integer', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_blocked' => ['required', 'string', 'in:yes,no'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
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
            'addresses.*.type' => ['required', 'string', Rule::in(ClientAddress::TYPES)],
            'addresses.*.name' => ['required', 'string', 'max:150'],
            'addresses.*.address' => ['required', 'string', 'max:500'],
            'addresses.*.city' => ['nullable', 'string', 'max:100'],
            'addresses.*.state' => ['nullable', 'string', 'max:100'],
            'addresses.*.country' => ['nullable', 'string', 'max:100'],
            'addresses.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'addresses.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'addresses.*.is_default' => ['required', 'string', 'in:yes,no'],
            'addresses.*.status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function clientMessages(): array
    {
        return [
            'name.required' => 'El nombre del cliente es obligatorio.',
            'document_type.required' => 'La letra del RIF es obligatoria.',
            'document_type.in' => 'La letra del RIF debe ser V, E, J, P, G o C.',
            'document_number.required' => 'El número de RIF o cédula es obligatorio.',
            'document_number.regex' => 'El número de RIF debe contener solo dígitos, sin letra ni guiones.',
            'document_number.unique' => 'Ya existe un cliente con ese RIF en la empresa.',
            'email.unique' => 'Ya existe un cliente con ese correo en la empresa.',
            'credit_blocked.required' => 'Indica si el cliente tiene el crédito bloqueado.',
            'discount_percent.max' => 'El descuento del cliente no puede superar el 100%.',
            'contacts.*.name.required' => 'El nombre del contacto es obligatorio.',
            'contacts.*.email.email' => 'El correo del contacto no tiene un formato válido.',
            'addresses.*.name.required' => 'El alias de la dirección es obligatorio.',
            'addresses.*.address.required' => 'La dirección es obligatoria.',
            'addresses.*.type.required' => 'Selecciona el tipo de dirección.',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez y no caben en `rules()`.
     */
    protected function validateClientInvariants(Validator $validator): void
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

        /** La dirección sugerida es una por tipo: una de facturación y una de entrega. */
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
