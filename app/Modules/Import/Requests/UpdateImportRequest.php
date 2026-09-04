<?php

declare(strict_types=1);

namespace App\Modules\Import\Requests;

use App\Modules\Import\Models\Import;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;
use App\Modules\Import\Requests\Concerns\ValidatesImportPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateImportRequest extends FormRequest
{
    use ValidatesImportPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('imports.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->importRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->importMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validateImportInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Confirmado ya generó su ajuste de revaluación:
     * se corrige anulándolo —después de anular el ajuste— y haciendo otro.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $import = app(ImportRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($import !== null && ! in_array($import->status, Import::EDITABLE_STATUSES, true)) {
            $validator->errors()->add(
                'status',
                'Solo se puede editar un expediente en borrador.',
            );
        }
    }
}
