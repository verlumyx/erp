<?php

declare(strict_types=1);

namespace App\Modules\Client\Requests;

use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStatusClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('clients.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * Un cliente con saldo por cobrar o con anticipos sin aplicar no se puede
     * desactivar: se dejaría deuda viva fuera del alcance de los documentos.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'inactive') {
                return;
            }

            $client = app(ClientRepositoryInterface::class)
                ->findById((string) $this->route('id'), session('current_company_id'));

            if ($client === null) {
                return;
            }

            if ((float) $client->current_balance !== 0.0 || (float) $client->advance_balance !== 0.0) {
                $validator->errors()->add(
                    'status',
                    'No se puede desactivar un cliente con saldo por cobrar o anticipos sin aplicar.',
                );
            }
        });
    }
}
