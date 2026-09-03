<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solo el Ajuste, la Entrada y el Despacho mueven existencia. Un documento
     * comercial describe un acuerdo con un tercero —lo que se debe, lo que se
     * cobra, lo que se acredita—, no un hecho físico, y el hecho físico lo
     * levanta siempre su documento logístico.
     *
     * Con esa regla la bandera sobra: la pregunta que respondía —«¿esta factura
     * mete la mercancía o ya entró antes?»— tiene ahora una sola respuesta, y
     * dejarla escrita solo servía para que alguien la pusiera en `yes` y el
     * stock entrara dos veces.
     */
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('affects_inventory');
            });
        }
    }

    public function down(): void
    {
        Schema::table('app_purchase_invoices', function (Blueprint $table): void {
            $table->enum('affects_inventory', ['yes', 'no'])->default('yes')->after('base_exchange_rate');
        });

        Schema::table('app_sales_invoices', function (Blueprint $table): void {
            $table->enum('affects_inventory', ['yes', 'no'])->default('yes')->after('base_exchange_rate');
        });

        Schema::table('app_purchase_credit_notes', function (Blueprint $table): void {
            $table->enum('affects_inventory', ['yes', 'no'])->default('no')->after('reason_detail');
        });

        Schema::table('app_sales_credit_notes', function (Blueprint $table): void {
            $table->enum('affects_inventory', ['yes', 'no'])->default('no')->after('reason_detail');
        });
    }

    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        return [
            'app_purchase_invoices',
            'app_sales_invoices',
            'app_purchase_credit_notes',
            'app_sales_credit_notes',
        ];
    }
};
