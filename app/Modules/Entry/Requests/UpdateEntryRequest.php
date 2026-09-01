<?php

declare(strict_types=1);

namespace App\Modules\Entry\Requests;

use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\Entry\Requests\Concerns\ValidatesEntryPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEntryRequest extends FormRequest
{
    use ValidatesEntryPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('entries.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->entryRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->entryMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStillEditable($validator);
            $this->validateEntryInvariants($validator);
        });
    }

    /**
     * Solo se edita en `draft`. Confirmada, la mercancía ya entró a la bodega y
     * el kardex lo tiene escrito: se corrige anulándola y registrando otra.
     */
    private function validateStillEditable(Validator $validator): void
    {
        $entry = app(EntryRepositoryInterface::class)
            ->findById((string) $this->route('id'), session('current_company_id'));

        if ($entry !== null && $entry->status !== 'draft') {
            $validator->errors()->add(
                'status',
                'Solo se puede editar una entrada en borrador.',
            );
        }
    }
}
