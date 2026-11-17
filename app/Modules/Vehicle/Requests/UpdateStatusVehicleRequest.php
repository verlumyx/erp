<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Requests;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('vehicles.update-status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Vehicle::STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'El nuevo estado es obligatorio.',
            'status.in' => 'El estado indicado no es válido.',
        ];
    }
}
