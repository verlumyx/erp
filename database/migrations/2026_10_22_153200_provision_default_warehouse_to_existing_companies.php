<?php

use App\Modules\Company\Models\Company;
use App\Modules\Warehouse\Services\WarehouseProvisionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $provisionService = app(WarehouseProvisionService::class);

        Company::query()->each(function (Company $company) use ($provisionService): void {
            $provisionService->provisionDefaults($company->id, $company->created_by);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not destructively remove warehouses
    }
};
