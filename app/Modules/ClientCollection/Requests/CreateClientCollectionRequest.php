<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Requests;

use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Requests\Concerns\ValidatesClientCollectionPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateClientCollectionRequest extends FormRequest
{
    use ValidatesClientCollectionPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('client-collections.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            /**
             * El origen se elige al crear y se congela. `advance` no está aquí:
             * ese cobro lo genera la aprobación de un anticipo.
             */
            'origin_type' => ['required', 'string', Rule::in(ClientCollection::CREATABLE_ORIGIN_TYPES)],
            'origin_id' => ['nullable', 'uuid'],
            ...$this->clientCollectionRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'origin_type.required' => 'Indica desde dónde se aplica el cobro.',
            'origin_type.in' => 'Un cobro solo se inicia desde un cliente o desde una factura.',
            ...$this->clientCollectionMessages(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateClientCollectionInvariants($validator));
    }
}
