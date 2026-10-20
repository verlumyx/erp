<?php

declare(strict_types=1);

namespace App\Modules\Pos\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClosePosSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pos-sessions.close') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'declared_cash_usd' => ['nullable', 'numeric', 'min:0'],
            'declared_cash_ves' => ['nullable', 'numeric', 'min:0'],
            'declared_card_ves' => ['nullable', 'numeric', 'min:0'],
            'declared_mobile_ves' => ['nullable', 'numeric', 'min:0'],
            'declared_other' => ['nullable', 'numeric', 'min:0'],
            'denominations' => ['nullable', 'array'],
            'denominations.*.currency' => ['required_with:denominations', 'string', 'in:USD,VES,EUR'],
            'denominations.*.denomination' => ['required_with:denominations', 'numeric', 'min:0'],
            'denominations.*.count' => ['required_with:denominations', 'integer', 'min:0'],
            'supervisor_id' => ['nullable', 'uuid', 'exists:users,id'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
