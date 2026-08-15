<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove role_id from users — role is per-company and stored in user_company
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        // Replace global unique on app_roles.name with per-company unique (name, company_id)
        Schema::table('app_roles', function (Blueprint $table) {
            $table->dropUnique('app_roles_name_unique');
            $table->unique(['name', 'company_id'], 'app_roles_name_company_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('app_roles', function (Blueprint $table) {
            $table->dropUnique('app_roles_name_company_id_unique');
            $table->unique('name', 'app_roles_name_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('role_id')->nullable()->constrained('app_roles')->nullOnDelete();
        });
    }
};
