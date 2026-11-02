<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_sales_invoices', function (Blueprint $table) {
            $table->uuid('price_list_id')->nullable()->after('warehouse_id');

            $table->foreign('price_list_id')
                ->references('id')
                ->on('app_price_lists')
                ->nullOnDelete();

            $table->index('price_list_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['price_list_id']);
            $table->dropIndex(['price_list_id']);
            $table->dropColumn('price_list_id');
        });
    }
};
