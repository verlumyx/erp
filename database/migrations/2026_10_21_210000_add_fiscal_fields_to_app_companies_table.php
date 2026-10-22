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
        Schema::table('app_companies', function (Blueprint $table) {
            $table->string('rif', 20)->nullable()->after('name');
            $table->string('fiscal_address', 500)->nullable()->after('address');
            $table->enum('taxpayer_type', ['ordinary', 'formal', 'special'])
                ->default('ordinary')
                ->after('fiscal_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_companies', function (Blueprint $table) {
            $table->dropColumn(['rif', 'fiscal_address', 'taxpayer_type']);
        });
    }
};
