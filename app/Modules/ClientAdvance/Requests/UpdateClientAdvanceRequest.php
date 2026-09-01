<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Requests;

use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use App\Modules\ClientAdvance\Requests\Concerns\ValidatesClientAdvancePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateClientAdvanceRequest extends FormRequest
{
    use ValidatesClientAdvancePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('client-advances.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->clientAdvanceRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->clientAdvanceMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateStillEditable($validator));
    }

    /**
     * Solo se edita en borrador. Aprobado el anticipo ya tiene un cobro espejo
     * que copió sus importes: para corregirlo hay que anular ese cobro, lo que
     * lo devuelve a borrador.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $advance = app(ClientAdvanceRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($advance === null) {
            return;
        }

        if (! in_array($advance->status, ClientAdvance::EDITABLE_STATUSES, true)) {
            $validator->errors()->add(
                'status',
                'Solo se puede editar un anticipo en borrador.',
            );
        }
    }
}
