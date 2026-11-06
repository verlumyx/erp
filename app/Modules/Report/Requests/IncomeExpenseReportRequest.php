<?php

declare(strict_types=1);

namespace App\Modules\Report\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomeExpenseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reports.income-expense') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'consulted' => ['nullable'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'report_basis' => ['nullable', 'in:cash_flow,accrual'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'currency_view' => ['nullable', 'in:base,secondary,both'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
