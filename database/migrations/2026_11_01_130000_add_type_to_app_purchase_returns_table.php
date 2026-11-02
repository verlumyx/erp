<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipo de devolución:
     * - definitive: devolución definitiva que origina o requiere nota de crédito y salda la factura.
     * - replacement: devolución por reposición o garantía; reabre la orden de compra para volver a recibir.
     */
    public function up(): void
    {
        Schema::table('app_purchase_returns', function (Blueprint $table) {
            $table->enum('type', ['definitive', 'replacement'])
                ->default('definitive')
                ->after('return_date');
        });
    }

    public function down(): void
    {
        Schema::table('app_purchase_returns', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
