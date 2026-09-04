<?php

declare(strict_types=1);

namespace App\Modules\Import\Requests;

use App\Modules\Import\Models\Import;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('imports.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Import::STATUSES)],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El nuevo estado es obligatorio.',
            'status.in' => 'El estado indicado no existe.',
        ];
    }

    /**
     * El expediente avanza por un camino fijo (`draft` → `confirmed` →
     * `completed`, o `cancelled`) y anular exige siempre un motivo. `completed`
     * no se elige aquí: lo escribe el ajuste al aplicarse.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            if ($status === 'cancelled' && trim((string) $this->input('cancellation_reason')) === '') {
                $validator->errors()->add(
                    'cancellation_reason',
                    'El motivo de anulación es obligatorio.',
                );
            }

            $import = app(ImportRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($import === null) {
                return;
            }

            $allowed = Import::STATUS_TRANSITIONS[$import->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar el expediente de «{$import->status}» a «{$status}».",
                );
            }
        });
    }
}
