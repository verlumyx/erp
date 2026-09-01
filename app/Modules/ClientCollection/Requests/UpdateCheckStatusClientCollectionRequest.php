<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Requests;

use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCheckStatusClientCollectionRequest extends FormRequest
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
            'check_status' => ['required', 'string', Rule::in(ClientCollection::CHECK_STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'check_status.required' => 'El nuevo estado del cheque es obligatorio.',
            'check_status.in' => 'El estado del cheque indicado no existe.',
        ];
    }

    /**
     * El cheque solo existe donde se cobró con uno, y un cobro anulado ya no
     * tiene cheque que seguir.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $collection = app(ClientCollectionRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($collection === null) {
                return;
            }

            if ($collection->payment_method !== 'check') {
                $validator->errors()->add(
                    'check_status',
                    'Este cobro no se recibió con cheque.',
                );
            }

            if ($collection->status === 'cancelled') {
                $validator->errors()->add(
                    'check_status',
                    'El cobro está anulado: su cheque ya no se mueve.',
                );
            }
        });
    }
}
