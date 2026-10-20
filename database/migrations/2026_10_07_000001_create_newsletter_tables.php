<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 180);
            $table->text('body');
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });
        Schema::create('newsletter_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_id')->constrained('newsletters')->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['newsletter_id', 'email']);
            $table->index(['newsletter_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_deliveries');
        Schema::dropIfExists('newsletters');
    }
};
