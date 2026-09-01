<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Requests;

use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use App\Modules\ClientCollection\Requests\Concerns\ValidatesClientCollectionPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateClientCollectionRequest extends FormRequest
{
    use ValidatesClientCollectionPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('client-collections.update') ?? false;
    }

    /**
     * El origen no está en las reglas a propósito: se congeló al crear el cobro
     * y lo que llegue en el payload se ignora.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->clientCollectionRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->clientCollectionMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validateClientCollectionInvariants($validator);
        });
    }

    /**
     * Solo se edita en borrador: confirmado el cobro ya movió el saldo de las
     * facturas y del cliente, y se corrige anulándolo y registrando otro.
     *
     * El cobro espejo de un anticipo no se edita nunca: monto, moneda, tasa,
     * cliente y forma de cobro son propiedad del anticipo.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $collection = app(ClientCollectionRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($collection === null) {
            return;
        }

        if ($collection->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar un cobro en borrador.',
            );
        }

        if ($collection->origin_type === 'advance') {
            $validator->errors()->add(
                'origin_type',
                'El cobro de un anticipo se corrige desde el anticipo, no aquí.',
            );
        }
    }
}
