<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_clients', function (Blueprint $table) {
            $table->uuid('company_id')->nullable()->after('id');
            $table->string('code', 12)->nullable()->after('company_id');

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->index('company_id');
        });

        $this->backfill();

        Schema::table('app_clients', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['company_id', 'email']);
            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_clients', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropUnique(['company_id', 'email']);
            $table->unique('email');

            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn(['company_id', 'code']);
        });
    }

    /**
     * Assign a company to every existing client (derived from its creator) and
     * generate a sequential per-company code (CLI000001, CLI000002, …).
     */
    private function backfill(): void
    {
        $fallbackCompanyId = DB::table('app_companies')->orderBy('created_at')->value('id');

        if ($fallbackCompanyId === null) {
            return;
        }

        $companyByCreator = [];

        $clients = DB::table('app_clients')
            ->orderBy('created_at')
            ->get(['id', 'created_by']);

        foreach ($clients as $client) {
            $companyId = $this->resolveCompanyId($client->created_by, $companyByCreator, $fallbackCompanyId);

            DB::table('app_clients')
                ->where('id', $client->id)
                ->update(['company_id' => $companyId]);
        }

        $companyIds = DB::table('app_clients')->distinct()->pluck('company_id');

        foreach ($companyIds as $companyId) {
            $sequence = 0;

            $companyClients = DB::table('app_clients')
                ->where('company_id', $companyId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id']);

            foreach ($companyClients as $companyClient) {
                $sequence++;
                $code = 'CLI'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);

                DB::table('app_clients')
                    ->where('id', $companyClient->id)
                    ->update(['code' => $code]);
            }
        }
    }

    /**
     * @param  array<string, string>  $cache
     */
    private function resolveCompanyId(?string $creatorId, array &$cache, string $fallbackCompanyId): string
    {
        if ($creatorId === null) {
            return $fallbackCompanyId;
        }

        if (isset($cache[$creatorId])) {
            return $cache[$creatorId];
        }

        $companyId = DB::table('user_company')
            ->where('user_id', $creatorId)
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->value('company_id');

        return $cache[$creatorId] = $companyId ?? $fallbackCompanyId;
    }
};
