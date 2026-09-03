<?php

declare(strict_types=1);

namespace App\Modules\Store\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('store-items.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'item_id' => ['required', 'uuid'],
            'title' => ['nullable', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9-]+$/'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'string', 'in:yes,no'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'El identificador es obligatorio.',
            'item_id.required' => 'Elige el artículo a publicar.',
            'item_id.uuid' => 'El artículo no es válido.',
            'title.max' => 'El título no puede superar 150 caracteres.',
            'slug.regex' => 'El slug solo admite minúsculas, números y guiones.',
            'summary.max' => 'El resumen no puede superar 300 caracteres.',
            'is_featured.in' => 'Indica si la publicación es destacada.',
            'order.integer' => 'El orden debe ser un número entero.',
        ];
    }
}
