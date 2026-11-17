<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Commands;

use App\Modules\Vehicle\Requests\CreateVehicleRequest;
use Illuminate\Support\Str;

class CreateVehicleCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $plate,
        public readonly string $name,
        public readonly ?string $brand = null,
        public readonly ?string $model = null,
        public readonly ?int $year = null,
        public readonly string $vehicleType = 'truck',
        public readonly float $capacityWeight = 0.0,
        public readonly float $capacityVolume = 0.0,
        public readonly ?string $notes = null,
        public readonly ?string $createdBy = null,
    ) {}

    public static function fromRequest(CreateVehicleRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->filled('id') ? $request->string('id')->toString() : (string) Str::uuid(),
            companyId: $companyId ?? (string) $request->route('company'),
            plate: strtoupper(trim($request->string('plate')->toString())),
            name: trim($request->string('name')->toString()),
            brand: $request->filled('brand') ? trim($request->string('brand')->toString()) : null,
            model: $request->filled('model') ? trim($request->string('model')->toString()) : null,
            year: $request->filled('year') ? $request->integer('year') : null,
            vehicleType: $request->string('vehicle_type', 'truck')->toString(),
            capacityWeight: (float) $request->input('capacity_weight', 0),
            capacityVolume: (float) $request->input('capacity_volume', 0),
            notes: $request->input('notes'),
            createdBy: $request->user()?->id,
        );
    }
}
