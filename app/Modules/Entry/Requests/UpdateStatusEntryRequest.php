<?php

declare(strict_types=1);

namespace App\Modules\Entry\Requests;

use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStatusEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('entries.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Entry::STATUSES)],
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
     * La entrada avanza por un camino fijo (`draft` → `confirmed` →
     * `completed`, o `cancelled`), y una que ya está facturada no se anula:
     * primero se anula la factura de compra que la respalda.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = (string) $this->input('status');

            $entry = app(EntryRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($entry === null) {
                return;
            }

            $allowed = Entry::STATUS_TRANSITIONS[$entry->status] ?? [];

            if (! in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede pasar la entrada de «{$entry->status}» a «{$status}».",
                );
            }

            if ($status === 'cancelled' && $entry->is_invoiced === 'yes') {
                $validator->errors()->add(
                    'status',
                    'No se puede anular una entrada ya respaldada por una factura de compra.',
                );
            }
        });
    }
}
