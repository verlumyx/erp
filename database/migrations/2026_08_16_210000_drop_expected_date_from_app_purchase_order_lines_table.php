<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La fecha estimada de entrega se acuerda para la orden completa, no línea a
 * línea: la cabecera ya la guarda en `app_purchase_orders.expected_date`.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn('expected_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_purchase_order_lines', function (Blueprint $table) {
            $table->date('expected_date')->nullable()->after('pending_quantity');
        });
    }
};
