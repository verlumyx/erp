<?php

use App\Modules\Company\Models\Company;
use App\Modules\MeasurementUnit\Services\MeasurementUnitProvisionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $provisionService = app(MeasurementUnitProvisionService::class);

        Company::query()->each(function (Company $company) use ($provisionService): void {
            $provisionService->provisionDefaults($company->id, $company->created_by);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not destructively remove measurement units
    }
};
