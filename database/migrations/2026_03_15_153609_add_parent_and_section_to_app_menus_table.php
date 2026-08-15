<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_menus', function (Blueprint $table) {
            $table->uuid('parent_id')->nullable()->after('id');
            $table->string('section', 20)->default('main')->after('order');
            $table->string('url', 255)->nullable()->change();
            $table->string('permission', 100)->nullable()->change();

            $table->foreign('parent_id')->references('id')->on('app_menus')->nullOnDelete();
            $table->index('section');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_menus', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['section']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['parent_id', 'section']);
        });
    }
};
