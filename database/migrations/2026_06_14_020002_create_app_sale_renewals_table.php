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
        Schema::create('app_sale_renewals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id');
            $table->date('renewed_at');
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->integer('duration_days');
            $table->decimal('price', 10, 2);
            $table->uuid('renewed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->nullable();

            // ON DELETE RESTRICT: el historial de renovaciones preserva la venta.
            $table->foreign('sale_id')
                ->references('id')
                ->on('app_sales')
                ->restrictOnDelete();

            // El histórico se preserva aunque se elimine el usuario.
            $table->foreign('renewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('sale_id', 'idx_sale_renewals_sale');
        });

        // CHECKs: SQLite no soporta ADD CONSTRAINT vía ALTER,
        // así que solo se aplican en motores que lo permiten (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_sale_renewals ADD CONSTRAINT app_sale_renewals_duration_days_check CHECK (duration_days >= 1)');
            DB::statement('ALTER TABLE app_sale_renewals ADD CONSTRAINT app_sale_renewals_price_check CHECK (price >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_sale_renewals');
    }
};
