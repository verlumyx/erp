<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Requests;

use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use App\Modules\Transfer\Requests\Concerns\ValidatesTransferPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTransferRequest extends FormRequest
{
    use ValidatesTransferPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transfers.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->transferRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->transferMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validateTransferInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Confirmado, la mercancía ya salió de la bodega
     * de origen y el kardex lo tiene escrito: se corrige anulándolo y emitiendo
     * otro.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $transfer = app(TransferRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($transfer !== null && $transfer->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar un traslado en borrador.',
            );
        }
    }
}
