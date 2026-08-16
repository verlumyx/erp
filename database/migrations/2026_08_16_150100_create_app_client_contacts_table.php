<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de detalle: sin `code`, se edita desde la pantalla del cliente.
     */
    public function up(): void
    {
        Schema::create('app_client_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('client_id');

            $table->string('name', 150);
            $table->string('position', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();

            /** Contacto principal del cliente: como máximo uno por cliente. */
            $table->enum('is_primary', ['yes', 'no'])->default('no');

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('app_companies')
                ->nullOnDelete();

            $table->foreign('client_id')
                ->references('id')
                ->on('app_clients')
                ->cascadeOnDelete();

            $table->index('client_id');
            $table->index('company_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_client_contacts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['client_id']);
        });

        Schema::dropIfExists('app_client_contacts');
    }
};
