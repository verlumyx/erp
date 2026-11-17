<?php

declare(strict_types=1);

namespace App\Modules\Report\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountsPayableReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reports.accounts-payable') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'consulted' => ['nullable'],
            'as_of_date' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'uuid'],
            'supplier_type_id' => ['nullable', 'uuid'],
            'payment_status' => ['nullable', 'in:all,overdue,pending,partial'],
            'currency_view' => ['nullable', 'in:base,document,secondary'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
