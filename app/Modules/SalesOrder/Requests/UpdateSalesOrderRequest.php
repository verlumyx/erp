<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Requests;

use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\SalesOrder\Requests\Concerns\ValidatesSalesOrderPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSalesOrderRequest extends FormRequest
{
    use ValidatesSalesOrderPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales-orders.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->salesOrderRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->salesOrderMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateSalesOrderInvariants($validator);
            $this->validateOrderIsEditable($validator);
        });
    }

    /**
     * Un pedido confirmado ya reserva inventario y compromete crédito: se anula
     * y se rehace, no se edita.
     */
    private function validateOrderIsEditable(Validator $validator): void
    {
        $order = app(SalesOrderRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($order === null || in_array($order->status, SalesOrder::EDITABLE_STATUSES, true)) {
            return;
        }

        $validator->errors()->add(
            'status',
            'Solo se puede editar un pedido en borrador.',
        );
    }
}
