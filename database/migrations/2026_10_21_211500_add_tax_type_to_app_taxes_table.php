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
        Schema::table('app_taxes', function (Blueprint $table) {
            $table->enum('tax_type', ['general', 'reduced', 'additional', 'exempt'])
                ->default('general')
                ->after('percentage');

            $table->index('tax_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_taxes', function (Blueprint $table) {
            $table->dropIndex(['tax_type']);
            $table->dropColumn('tax_type');
        });
    }
};
