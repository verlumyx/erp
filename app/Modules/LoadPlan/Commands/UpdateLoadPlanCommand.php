<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Commands;

use App\Modules\LoadPlan\Requests\UpdateLoadPlanRequest;

class UpdateLoadPlanCommand
{
    /**
     * @param  array<int, LoadPlanDispatchData>  $dispatches
     */
    public function __construct(
        public readonly string $planDate,
        public readonly string $routeId,
        public readonly string $warehouseId,
        public readonly string $vehicleId,
        public readonly string $driverName,
        public readonly ?string $driverId = null,
        public readonly ?string $helperName = null,
        public readonly ?string $description = null,
        public readonly array $dispatches = [],
    ) {}

    public static function fromRequest(UpdateLoadPlanRequest $request): self
    {
        $rawDispatches = $request->input('dispatches', []);
        $dispatches = [];

        foreach ($rawDispatches as $index => $row) {
            if (! empty($row['dispatch_id'])) {
                $dispatches[] = LoadPlanDispatchData::fromArray($row, $index);
            }
        }

        return new self(
            planDate: $request->string('plan_date')->toString(),
            routeId: $request->string('route_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            vehicleId: $request->string('vehicle_id')->toString(),
            driverName: trim($request->string('driver_name')->toString()),
            driverId: $request->filled('driver_id') ? $request->string('driver_id')->toString() : null,
            helperName: $request->filled('helper_name') ? trim($request->string('helper_name')->toString()) : null,
            description: $request->input('description'),
            dispatches: $dispatches,
        );
    }
}
