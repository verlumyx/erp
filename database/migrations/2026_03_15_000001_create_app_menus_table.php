<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_menus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 100);
            $table->string('url', 255);
            $table->string('permission', 100);
            $table->string('icon', 100);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('permission');
            $table->index('is_active');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_menus');
    }
};
