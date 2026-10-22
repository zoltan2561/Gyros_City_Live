<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('time', 'online_order_until')) {
            Schema::table('time', fn (Blueprint $table) => $table->string('online_order_until', 5)->default('21:30'));
        }
    }

    public function down(): void
    {
        Schema::table('time', fn (Blueprint $table) => $table->dropColumn('online_order_until'));
    }
};
