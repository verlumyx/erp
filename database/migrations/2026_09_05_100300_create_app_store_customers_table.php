<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comprador de la tienda: la cuenta con la que alguien compra. No es un
     * cliente del ERP, es quien puede llegar a serlo. El vínculo con
     * `app_clients` vive aquí y se resuelve una sola vez por comprador.
     */
    public function up(): void
    {
        Schema::create('app_store_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code', 12)->nullable();

            /** El vínculo con el cliente del ERP. Nulo hasta que se resuelve. */
            $table->uuid('client_id')->nullable();

            $table->string('name', 150);

            /** Identificador de inicio de sesión. Único por empresa. */
            $table->string('email', 150);
            $table->string('phone', 30)->nullable();

            /** Letra del RIF. Opcional al registrarse; obligatorio para convertir. */
            $table->enum('document_type', ['V', 'E', 'J', 'P', 'G', 'C'])->nullable();
            $table->string('document_number', 15)->nullable();

            /** Nulo mientras la cuenta está `invited`. */
            $table->string('password_hash', 255)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            /** Cómo y cuándo se resolvió `client_id`. */
            $table->timestamp('linked_at')->nullable();
            $table->uuid('linked_by')->nullable();
            $table->enum('link_source', ['rif', 'invitation', 'conversion', 'manual'])->nullable();

            /** SHA-256 del token de invitación; se borra al aceptar. */
            $table->string('invitation_token_hash', 64)->nullable();
            $table->timestamp('invitation_expires_at')->nullable();

            $table->enum('status', ['invited', 'active', 'inactive'])->default('active');
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

            $table->foreign('linked_by')
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
            $table->unique(['company_id', 'code']);

            // Índices propios del módulo
            $table->unique(['company_id', 'email']);
            $table->unique(['company_id', 'document_type', 'document_number']);
            /** Un cliente tiene como máximo un comprador. */
            $table->unique('client_id');
            $table->index('email');
            $table->index('invitation_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('app_store_customers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['linked_by']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('app_store_customers');
    }
};
