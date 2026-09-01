<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Umbral a partir del cual un ajuste de inventario necesita que lo apruebe
     * alguien distinto de quien lo registró.
     *
     * Se compara contra el valor absoluto del impacto (`net_cost`): un faltante
     * grande merece un segundo par de ojos igual que un sobrante grande. El
     * cero por defecto exige esa segunda firma en cuanto el ajuste mueve valor.
     */
    public function up(): void
    {
        Schema::table('app_configurations', function (Blueprint $table) {
            $table->decimal('adjustment_approval_threshold', 18, 2)
                ->default(0)
                ->after('price_decimals');
        });
    }

    public function down(): void
    {
        Schema::table('app_configurations', function (Blueprint $table) {
            $table->dropColumn('adjustment_approval_threshold');
        });
    }
};
