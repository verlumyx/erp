<?php

declare(strict_types=1);

namespace App\Modules\Item\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('items.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo Excel para importar.',
            'file.file' => 'El archivo subido no es válido.',
            'file.mimes' => 'El archivo debe ser un documento de Excel (.xlsx, .xls) o CSV.',
            'file.max' => 'El archivo no debe exceder los 10 MB.',
        ];
    }
}
