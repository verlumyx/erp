<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusMeasurementUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('measurement-units.update-status') ?? false;
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
