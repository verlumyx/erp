<?php

declare(strict_types=1);

namespace App\Modules\Item\Requests;

use App\Modules\Item\Services\ItemUsageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStatusItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('items.update-status') ?? false;
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
     * Un artículo con existencia distinta de cero o con documentos abiertos no
     * se puede desactivar: se dejaría mercancía real —o promesas de mercancía—
     * fuera del catálogo (`docs/inventario.md` §1). Es la misma regla que
     * protege a un cliente con saldo o a un proveedor con deuda.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'inactive') {
                return;
            }

            $companyId = session('current_company_id');
            $itemId = (string) $this->route('id');
            $usage = app(ItemUsageService::class);

            if ($usage->stockedQuantity($companyId, $itemId) !== 0.0) {
                $validator->errors()->add(
                    'status',
                    'No se puede desactivar un artículo con existencia distinta de cero.',
                );

                return;
            }

            if ($usage->hasOpenDocuments($companyId, $itemId)) {
                $validator->errors()->add(
                    'status',
                    'No se puede desactivar un artículo con documentos abiertos.',
                );
            }
        });
    }
}
