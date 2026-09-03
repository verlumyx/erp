<?php

declare(strict_types=1);

namespace App\Modules\Store\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadStoreItemImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('store-items.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Selecciona al menos una foto.',
            'images.min' => 'Selecciona al menos una foto.',
            'images.*.mimes' => 'Las fotos deben ser JPG, PNG o WebP.',
            'images.*.max' => 'Cada foto puede pesar como máximo 5 MB.',
            'alt_text.max' => 'El texto alternativo no puede superar 150 caracteres.',
        ];
    }
}
