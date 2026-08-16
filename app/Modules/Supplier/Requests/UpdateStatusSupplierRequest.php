<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Requests;

use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStatusSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('suppliers.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * Un proveedor con saldo por pagar o con anticipos sin aplicar no se puede
     * desactivar: se dejaría deuda viva fuera del alcance de los documentos.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'inactive') {
                return;
            }

            $supplier = app(SupplierRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($supplier === null) {
                return;
            }

            if ((float) $supplier->current_balance !== 0.0 || (float) $supplier->advance_balance !== 0.0) {
                $validator->errors()->add(
                    'status',
                    'No se puede desactivar un proveedor con saldo por pagar o anticipos sin aplicar.',
                );
            }
        });
    }
}
