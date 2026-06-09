<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasIndex('categories', ['user_id', 'id'], 'unique')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->unique(['user_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasIndex('categories', ['user_id', 'id'], 'unique')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'id']);
        });
    }
};
