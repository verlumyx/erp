<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dispatches.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Dispatch::STATUSES)],
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
     * El despacho avanza por un camino fijo (`draft` → `confirmed` →
     * `completed`, o `cancelled`), y no se da por cumplido un viaje del que
     * todavía no se sabe cómo terminó.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            $dispatch = app(DispatchRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($dispatch === null) {
                return;
            }

            $allowed = Dispatch::STATUS_TRANSITIONS[$dispatch->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar el despacho de «{$dispatch->status}» a «{$status}».",
                );

                return;
            }

            if ($status === 'completed' && ! $dispatch->isDeliverySettled()) {
                $validator->errors()->add(
                    'status',
                    'Registra primero cómo terminó la entrega.',
                );
            }
        });
    }
}
