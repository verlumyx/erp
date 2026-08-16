<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('exchange-rates.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }
}
