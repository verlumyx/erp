<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arqueo detallado de denominaciones: conteo físico billete por billete.
     */
    public function up(): void
    {
        Schema::create('app_pos_session_denominations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('pos_session_id');
            $table->string('currency', 3);
            $table->decimal('denomination', 18, 2);
            $table->integer('count')->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('pos_session_id')
                ->references('id')
                ->on('app_pos_sessions')
                ->cascadeOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_at');

            // Índices propios
            $table->index('pos_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_pos_session_denominations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['pos_session_id']);
        });

        Schema::dropIfExists('app_pos_session_denominations');
    }
};
