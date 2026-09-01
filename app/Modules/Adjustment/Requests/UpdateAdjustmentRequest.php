<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Requests;

use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use App\Modules\Adjustment\Requests\Concerns\ValidatesAdjustmentPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAdjustmentRequest extends FormRequest
{
    use ValidatesAdjustmentPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('adjustments.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->adjustmentRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->adjustmentMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validateAdjustmentInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Enviado a aprobación, lo que alguien está por
     * firmar deja de moverse; confirmado, la existencia ya cambió y el kardex
     * lo tiene escrito: se corrige anulándolo y registrando otro.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $adjustment = app(AdjustmentRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($adjustment !== null && $adjustment->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar un ajuste en borrador.',
            );
        }
    }
}
