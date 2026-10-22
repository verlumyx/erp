<?php

use App\Modules\Client\Services\ClientProvisionService;
use App\Modules\Company\Models\Company;
use App\Modules\PriceList\Services\PriceListProvisionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $priceListProvision = app(PriceListProvisionService::class);
        $clientProvision = app(ClientProvisionService::class);

        Company::query()->each(function (Company $company) use ($priceListProvision, $clientProvision): void {
            $priceList = $priceListProvision->provisionDefaults($company->id, $company->created_by);
            $clientProvision->provisionDefaults($company->id, $company->created_by, $priceList->id);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not destructively remove price lists or clients
    }
};
