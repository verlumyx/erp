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
        Schema::create('app_manual_transaction_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('manual_transaction_id');
            // type se deriva de category, pero se persiste para reportar sin recomputar.
            $table->string('type', 10);
            $table->string('category', 40);
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->timestampsTz();

            // ON DELETE CASCADE: las líneas viven y mueren con su cabecera.
            $table->foreign('manual_transaction_id')
                ->references('id')
                ->on('app_manual_transactions')
                ->cascadeOnDelete();

            $table->index('manual_transaction_id', 'idx_manual_transaction_lines_parent');
        });

        // CHECKs: solo en motores que soportan ADD CONSTRAINT vía ALTER (PostgreSQL).
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE app_manual_transaction_lines ADD CONSTRAINT app_manual_transaction_lines_type_check CHECK (type IN ('income','expense'))");
            DB::statement('ALTER TABLE app_manual_transaction_lines ADD CONSTRAINT app_manual_transaction_lines_amount_check CHECK (amount >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_manual_transaction_lines');
    }
};
