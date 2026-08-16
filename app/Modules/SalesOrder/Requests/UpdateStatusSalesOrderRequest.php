<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Requests;

use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-orders.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(SalesOrder::STATUSES)],
            'cancellation_reason' => [
                Rule::requiredIf(fn (): bool => $this->input('status') === 'cancelled'),
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Indica el nuevo estado del pedido.',
            'status.in' => 'El estado indicado no es válido para un pedido de venta.',
            'cancellation_reason.required' => 'Indica el motivo de la anulación.',
        ];
    }

    /**
     * El ciclo del pedido es dirigido: draft → confirmed → partial → completed,
     * con `cancelled` como salida. Un pedido cumplido o anulado ya es final.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $order = app(SalesOrderRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($order === null) {
                return;
            }

            $allowed = SalesOrder::STATUS_TRANSITIONS[$order->status] ?? [];

            if (! in_array((string) $this->input('status'), $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    'No se puede pasar un pedido de "'.$order->status.'" a "'.$this->input('status').'".',
                );
            }

            if ($this->input('status') === 'confirmed' && $order->lines->where('status', 'active')->isEmpty()) {
                $validator->errors()->add(
                    'status',
                    'No se puede confirmar un pedido sin líneas activas.',
                );
            }
        });
    }
}
