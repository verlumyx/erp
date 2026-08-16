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
        Schema::create('app_suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('supplier_type_id')->nullable();

            $table->string('name', 200);
            $table->string('legal_name', 200)->nullable();

            /** Letra del RIF; la naturaleza del contribuyente se deriva de ella. */
            $table->enum('document_type', ['V', 'E', 'J', 'P', 'G', 'C'])->default('J');
            $table->string('document_number', 15);

            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('website', 255)->nullable();

            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();

            $table->string('currency', 3)->default('USD');
            $table->integer('payment_term_days')->default(0);
            $table->decimal('credit_limit', 18, 2)->default(0);

            /**
             * Saldos derivados: los recalcula el sistema al confirmar facturas,
             * notas de crédito, anticipos y pagos. Nunca se editan a mano.
             */
            $table->decimal('current_balance', 18, 2)->default(0);
            $table->decimal('advance_balance', 18, 2)->default(0);

            $table->integer('lead_time_days')->default(0);
            $table->text('notes')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('supplier_type_id')
                ->references('id')
                ->on('app_supplier_types')
                ->nullOnDelete();

            /** Foreign key: quién registró el proveedor (trazabilidad) */
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('name');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            // Índices propios del módulo
            $table->index('supplier_type_id');
            $table->index('current_balance');
            $table->index('document_number');

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'document_type', 'document_number']);
            $table->unique(['company_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_suppliers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['supplier_type_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_suppliers');
    }
};
