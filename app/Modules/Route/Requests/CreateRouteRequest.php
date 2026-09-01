<?php

declare(strict_types=1);

namespace App\Modules\Route\Requests;

use App\Modules\Route\Requests\Concerns\ValidatesRoutePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateRouteRequest extends FormRequest
{
    use ValidatesRoutePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('routes.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->routeRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->routeMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateRouteInvariants($validator));
    }
}
