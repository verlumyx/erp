<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Requests;

use App\Modules\ClientAdvance\Requests\Concerns\ValidatesClientAdvancePayload;
use Illuminate\Foundation\Http\FormRequest;

class CreateClientAdvanceRequest extends FormRequest
{
    use ValidatesClientAdvancePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('client-advances.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...$this->clientAdvanceRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->clientAdvanceMessages();
    }
}
