<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Requests;

use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;
use App\Modules\SupplierAdvance\Requests\Concerns\ValidatesSupplierAdvancePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSupplierAdvanceRequest extends FormRequest
{
    use ValidatesSupplierAdvancePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier-advances.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->supplierAdvanceRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->supplierAdvanceMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateStillEditable($validator));
    }

    /**
     * Solo se edita en borrador. Aprobado el anticipo ya tiene un pago espejo
     * que copió sus importes: para corregirlo hay que anular ese pago, lo que
     * lo devuelve a borrador.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $advance = app(SupplierAdvanceRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($advance === null) {
            return;
        }

        if (! in_array($advance->status, SupplierAdvance::EDITABLE_STATUSES, true)) {
            $validator->errors()->add(
                'status',
                'Solo se puede editar un anticipo en borrador.',
            );
        }
    }
}
