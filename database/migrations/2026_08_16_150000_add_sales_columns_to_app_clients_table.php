<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Extiende `app_clients` con la información comercial necesaria para
     * facturar: identificación fiscal, condiciones de crédito, saldos
     * derivados y asignación de vendedor y ruta.
     */
    public function up(): void
    {
        Schema::table('app_clients', function (Blueprint $table) {
            $table->uuid('client_type_id')->nullable()->after('code');
            $table->uuid('price_list_id')->nullable()->after('client_type_id');

            $table->string('legal_name', 200)->nullable()->after('name');
            $table->enum('document_type', ['V', 'E', 'J', 'P', 'G', 'C'])
                ->default('V')
                ->after('legal_name');

            /** Se crea nulable para poder rellenar las filas existentes; abajo pasa a NOT NULL. */
            $table->string('document_number', 15)->nullable()->after('document_type');

            $table->string('mobile', 30)->nullable()->after('phone');
            $table->string('address', 500)->nullable()->after('email');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('country', 100)->nullable()->after('state');

            $table->integer('payment_term_days')->default(0)->after('country');
            $table->decimal('credit_limit', 18, 2)->default(0)->after('payment_term_days');
            $table->enum('credit_blocked', ['yes', 'no'])->default('no')->after('credit_limit');

            /** Derivados: los mantiene el sistema al confirmar documentos. */
            $table->decimal('current_balance', 18, 2)->default(0)->after('credit_blocked');
            $table->decimal('advance_balance', 18, 2)->default(0)->after('current_balance');

            $table->decimal('discount_percent', 7, 4)->default(0)->after('advance_balance');
            $table->uuid('salesperson_id')->nullable()->after('discount_percent');

            /**
             * Ruta de entrega habitual. La foreign key contra app_routes se
             * agrega junto con el módulo de Rutas (Logística), que aún no existe.
             */
            $table->uuid('route_id')->nullable()->after('salesperson_id');

            $table->decimal('latitude', 10, 7)->nullable()->after('route_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            $table->foreign('client_type_id')
                ->references('id')
                ->on('app_client_types')
                ->nullOnDelete();

            $table->foreign('price_list_id')
                ->references('id')
                ->on('app_price_lists')
                ->nullOnDelete();

            $table->foreign('salesperson_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('client_type_id');
            $table->index('price_list_id');
            $table->index('salesperson_id');
            $table->index('route_id');
            $table->index('current_balance');
            $table->index('document_number');
        });

        $this->backfillDocumentNumber();

        Schema::table('app_clients', function (Blueprint $table) {
            $table->string('document_number', 15)->nullable(false)->change();

            $table->unique(['company_id', 'document_type', 'document_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_clients', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'document_type', 'document_number']);

            $table->dropForeign(['client_type_id']);
            $table->dropForeign(['price_list_id']);
            $table->dropForeign(['salesperson_id']);

            $table->dropIndex(['client_type_id']);
            $table->dropIndex(['price_list_id']);
            $table->dropIndex(['salesperson_id']);
            $table->dropIndex(['route_id']);
            $table->dropIndex(['current_balance']);
            $table->dropIndex(['document_number']);

            $table->dropColumn([
                'client_type_id',
                'price_list_id',
                'legal_name',
                'document_type',
                'document_number',
                'mobile',
                'address',
                'city',
                'state',
                'country',
                'payment_term_days',
                'credit_limit',
                'credit_blocked',
                'current_balance',
                'advance_balance',
                'discount_percent',
                'salesperson_id',
                'route_id',
                'latitude',
                'longitude',
            ]);
        });
    }

    /**
     * Los clientes que ya existían no tienen RIF. Se rellenan con los dígitos
     * de su `code` (CLI000001 → 000001): es único por empresa, cumple el
     * unique y se distingue a simple vista como un valor por completar.
     */
    private function backfillDocumentNumber(): void
    {
        $clients = DB::table('app_clients')
            ->whereNull('document_number')
            ->get(['id', 'code']);

        foreach ($clients as $client) {
            $digits = preg_replace('/\D/', '', (string) $client->code);

            if ($digits === '') {
                $digits = substr(preg_replace('/\D/', '', (string) $client->id), 0, 15);
            }

            DB::table('app_clients')
                ->where('id', $client->id)
                ->update(['document_number' => $digits === '' ? '0' : $digits]);
        }
    }
};
