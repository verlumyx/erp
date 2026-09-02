<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Requests;

use App\Modules\Warehouse\Services\WarehouseUsageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStatusWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('warehouses.update-status') ?? false;
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
     * Una bodega con existencia distinta de cero o con pedidos pendientes no se
     * puede desactivar: se dejaría mercancía real —o promesas de mercancía—
     * fuera del alcance de los documentos (`docs/inventario.md` §2).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'inactive') {
                return;
            }

            $companyId = session('current_company_id');
            $warehouseId = (string) $this->route('id');
            $usage = app(WarehouseUsageService::class);

            if ($usage->stockedQuantity($companyId, $warehouseId) !== 0.0) {
                $validator->errors()->add(
                    'status',
                    'No se puede desactivar una bodega con existencia distinta de cero.',
                );

                return;
            }

            if ($usage->hasOpenDocuments($companyId, $warehouseId)) {
                $validator->errors()->add(
                    'status',
                    'No se puede desactivar una bodega con pedidos pendientes.',
                );
            }
        });
    }
}
