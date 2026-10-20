<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->unsignedInteger('category_id')->nullable();
            $table->json('trigger_item_ids')->nullable();
            $table->unsignedInteger('required_qty');
            $table->json('gift_item_ids');
            $table->unsignedInteger('gift_qty')->default(1);
            $table->boolean('repeat')->default(false);
            $table->boolean('active')->default(false)->index();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_promotions');
    }
};
