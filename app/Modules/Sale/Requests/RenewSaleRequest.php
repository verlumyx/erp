<?php

declare(strict_types=1);

namespace App\Modules\Sale\Requests;

use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RenewSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales.renew') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Solo se puede renovar una venta activa o expirada dentro del periodo de gracia.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $sale = Sale::query()
                ->where('id', $this->route('id'))
                ->where('company_id', session('current_company_id'))
                ->first();

            if ($sale === null) {
                return;
            }

            if (! $sale->canBeRenewed()) {
                $validator->errors()->add(
                    'id',
                    'Esta venta no puede renovarse en su estado actual. Usa la reactivación.'
                );
            }
        });
    }
}
