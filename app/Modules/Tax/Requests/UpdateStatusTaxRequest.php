<?php

declare(strict_types=1);

namespace App\Modules\Tax\Requests;

use App\Modules\Item\Models\Item;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('taxes.update-status') ?? false;
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
     * Un impuesto en uso no se puede desactivar: los artículos activos que lo
     * tienen asignado se quedarían sin impuesto por defecto.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('status') !== 'inactive') {
                    return;
                }

                $taxId = $this->route('id');

                $isAssigned = Item::query()
                    ->where('company_id', session('current_company_id'))
                    ->where('status', 'active')
                    ->where(function ($query) use ($taxId): void {
                        $query->where('sale_tax_id', $taxId)
                            ->orWhere('purchase_tax_id', $taxId);
                    })
                    ->exists();

                if ($isAssigned) {
                    $validator->errors()->add(
                        'status',
                        'El impuesto está asignado a artículos activos: no se puede desactivar.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es válido.',
        ];
    }
}
