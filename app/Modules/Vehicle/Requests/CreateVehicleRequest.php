<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Requests;

use App\Modules\Vehicle\Requests\Concerns\ValidatesVehiclePayload;
use Illuminate\Foundation\Http\FormRequest;

class CreateVehicleRequest extends FormRequest
{
    use ValidatesVehiclePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('vehicles.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'uuid'],
            ...$this->vehicleRules(),
        ];
    }
}
