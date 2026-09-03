<?php

declare(strict_types=1);

namespace App\Modules\Store\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreItemRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9-]+$/'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string'],
            'is_featured' => ['required', 'string', 'in:yes,no'],
            'order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar 150 caracteres.',
            'slug.regex' => 'El slug solo admite minúsculas, números y guiones.',
            'summary.max' => 'El resumen no puede superar 300 caracteres.',
            'is_featured.required' => 'Indica si la publicación es destacada.',
            'is_featured.in' => 'Indica si la publicación es destacada.',
            'order.required' => 'El orden es obligatorio.',
            'order.integer' => 'El orden debe ser un número entero.',
        ];
    }
}
