<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Requests;

use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusClientCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('client-collections.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(ClientCollection::STATUSES)],
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
     * El cobro avanza por un camino fijo (`draft` → `confirmed` → `completed`,
     * o `cancelled`) y anular exige siempre un motivo.
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

            $collection = app(ClientCollectionRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($collection === null) {
                return;
            }

            $allowed = ClientCollection::STATUS_TRANSITIONS[$collection->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar el cobro de «{$collection->status}» a «{$status}».",
                );
            }
        });
    }
}
