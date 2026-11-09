<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('signage_screens',fn(Blueprint $t)=>$t->unsignedInteger('offline_price_seconds')->default(1800)); }
    public function down(): void { Schema::table('signage_screens',fn(Blueprint $t)=>$t->dropColumn('offline_price_seconds')); }
};
