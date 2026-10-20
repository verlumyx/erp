<?php

declare(strict_types=1);

namespace App\Modules\Pos\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePosRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pos-registers.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');
        $registerId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('app_pos_registers', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($registerId),
            ],
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('app_warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'price_list_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_price_lists', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'default_client_id' => [
                'required',
                'uuid',
                Rule::exists('app_clients', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'receipt_printer_name' => ['nullable', 'string', 'max:100'],
            'invoice_series' => ['nullable', 'string', 'max:20'],
            'allow_price_override' => ['nullable', 'string', 'in:yes,no'],
            'allow_custom_discounts' => ['nullable', 'string', 'in:yes,no'],
            'max_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }
}
