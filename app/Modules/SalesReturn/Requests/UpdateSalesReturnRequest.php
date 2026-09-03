<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Requests;

use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use App\Modules\SalesReturn\Requests\Concerns\ValidatesSalesReturnPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSalesReturnRequest extends FormRequest
{
    use ValidatesSalesReturnPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-returns.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->salesReturnRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->salesReturnMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validateSalesReturnInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Confirmada, la factura de origen ya tiene
     * apuntado lo devuelto: se corrige anulándola y emitiendo otra.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $return = app(SalesReturnRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($return !== null && $return->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar una devolución en borrador.',
            );
        }
    }
}
