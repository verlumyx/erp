<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Dispatch\Requests\Concerns\ValidatesDispatchPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDispatchRequest extends FormRequest
{
    use ValidatesDispatchPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dispatches.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->dispatchRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->dispatchMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validateDispatchInvariants($validator);
        });
    }

    /**
     * Solo se edita en estados no publicados (`draft` o `pending`). Confirmado, la mercancía ya salió de la bodega
     * y el kardex lo tiene escrito: se corrige anulándolo y emitiendo otro.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $dispatch = app(DispatchRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($dispatch !== null && ! in_array($dispatch->status, Dispatch::EDITABLE_STATUSES, true)) {
            $validator->errors()->add(
                'status',
                'Solo se puede editar un despacho en borrador o pendiente.',
            );
        }
    }
}
