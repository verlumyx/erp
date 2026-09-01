<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera del cobro a cliente. Es la entrada de dinero que cancela una o
     * varias facturas de venta; el reparto vive en
     * `app_client_collection_applications`.
     */
    public function up(): void
    {
        Schema::create('app_client_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            $table->uuid('client_id');

            /**
             * Desde dónde se inició el cobro. Se congela al crearlo y ya no
             * cambia: es lo que decide qué ofrece la pantalla. `advance` no es
             * un camino de creación —esos cobros nacen al aprobar un `ANC`—,
             * pero sí un origen posible.
             */
            $table->enum('origin_type', ['client', 'invoice', 'advance'])->default('client');

            /**
             * Id de la factura o del anticipo. Nulo con `origin_type = client`.
             * Sin foreign key: apunta a dos tablas distintas según el tipo, y
             * lo valida el Service antes de guardar.
             */
            $table->uuid('origin_id')->nullable();

            $table->date('collection_date');
            $table->enum('payment_method', ['cash', 'transfer', 'check', 'card', 'advance', 'credit_note', 'other'])
                ->default('cash');
            $table->string('reference', 60)->nullable();
            $table->string('bank_account', 60)->nullable();

            /** Cobrador o vendedor que recibió el dinero. */
            $table->uuid('collected_by')->nullable();

            /**
             * Ruta en la que se cobró. La foreign key contra app_routes se
             * agrega junto con el módulo de Rutas (Logística), que aún no existe.
             */
            $table->uuid('route_id')->nullable();

            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('base_currency', 3)->nullable();
            $table->decimal('base_exchange_rate', 18, 8)->nullable();

            $table->decimal('amount', 18, 2)->default(0);
            /** Retención soportada por el cliente: cancela deuda sin entrar en caja. */
            $table->decimal('withholding_amount', 18, 2)->default(0);
            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->decimal('unapplied_amount', 18, 2)->default(0);

            /**
             * Importe en bolívares congelado: el cobro tiene valor legal y lo
             * que entró en moneda local no se recalcula nunca. Contra el de la
             * factura sale el diferencial cambiario de cada aplicación.
             */
            $table->decimal('amount_ves', 18, 2)->default(0);

            /** Cheques: su propio ciclo, aparte del estado del documento. */
            $table->string('check_number', 30)->nullable();
            $table->date('check_date')->nullable();
            $table->enum('check_status', ['pending', 'deposited', 'cleared', 'bounced'])->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])
                ->default('draft');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->restrictOnDelete();

            $table->foreign('collected_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Índices base
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');

            // Índices propios del módulo
            $table->index('client_id');
            $table->index('collection_date');
            $table->index('payment_method');
            $table->index('collected_by');
            $table->index('route_id');
            $table->index('check_status');
            $table->index(['origin_type', 'origin_id']);

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('app_client_collections', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['collected_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_client_collections');
    }
};
