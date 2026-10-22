<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('settings', 'reviews_enabled')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('reviews_enabled')->default(2);
            });
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('reviews_enabled');
        });
    }
};
