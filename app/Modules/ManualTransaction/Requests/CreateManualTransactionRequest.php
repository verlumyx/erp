<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Requests;

use App\Modules\Transaction\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateManualTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manual-transactions.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categories = array_merge(
            Transaction::INCOME_CATEGORIES,
            Transaction::EXPENSE_CATEGORIES,
        );

        return [
            'id' => ['required', 'uuid'],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:10'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.category' => ['required', 'string', Rule::in($categories)],
            'lines.*.amount' => ['required', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'Debe registrar al menos una línea.',
            'lines.min' => 'Debe registrar al menos una línea.',
            'lines.*.category.required' => 'La categoría de la línea es obligatoria.',
            'lines.*.category.in' => 'La categoría de la línea no es válida.',
            'lines.*.amount.required' => 'El monto de la línea es obligatorio.',
            'lines.*.amount.min' => 'El monto de la línea no puede ser negativo.',
        ];
    }
}
