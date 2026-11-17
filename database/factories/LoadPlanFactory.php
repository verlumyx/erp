<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\Route\Models\Route;
use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\LoadPlan\Models\LoadPlan>
 */
class LoadPlanFactory extends Factory
{
    protected $model = LoadPlan::class;

    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = ++self::$sequence;

        return [
            'company_id' => Company::factory(),
            'code' => 'PLC'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'plan_date' => now()->toDateString(),
            'route_id' => Route::factory(),
            'warehouse_id' => Warehouse::factory(),
            'vehicle_id' => Vehicle::factory(),
            'driver_name' => fake()->name(),
            'driver_id' => null,
            'helper_name' => fake()->name().' / '.fake()->name(),
            'description' => fake()->optional()->sentence(),
            'total_dispatches' => 0,
            'total_weight' => 0,
            'total_volume' => 0,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }
}
