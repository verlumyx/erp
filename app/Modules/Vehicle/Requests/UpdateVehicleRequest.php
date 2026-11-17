<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Requests;

use App\Modules\Vehicle\Requests\Concerns\ValidatesVehiclePayload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    use ValidatesVehiclePayload;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('vehicles.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->vehicleRules();
    }
}
