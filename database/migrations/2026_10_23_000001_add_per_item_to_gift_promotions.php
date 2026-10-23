<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gift_promotions')) {
            return;
        }
        if (!Schema::hasColumn('gift_promotions', 'per_item')) {
            Schema::table('gift_promotions', function (Blueprint $table) {
                $table->boolean('per_item')->default(false)->after('repeat');
            });
        }
        DB::table('gift_promotions')->where('name', 'like', '%palacsinta%')->update([
            'per_item' => true,
            'required_qty' => 2,
            'gift_qty' => 1,
        ]);
        DB::table('gift_promotions')->where('name', 'Két pizza mellé ajándék palacsinta')->update([
            'name' => '2 pizzától minden pizzához egy palacsinta',
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('gift_promotions') || !Schema::hasColumn('gift_promotions', 'per_item')) {
            return;
        }
        DB::table('gift_promotions')->where('name', '2 pizzától minden pizzához egy palacsinta')->update([
            'name' => 'Két pizza mellé ajándék palacsinta',
        ]);
        Schema::table('gift_promotions', function (Blueprint $table) {
            $table->dropColumn('per_item');
        });
    }
};
