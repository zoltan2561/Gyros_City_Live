<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signage_jobs',function(Blueprint $t){
            $t->bigIncrements('id'); $t->string('queue')->index(); $t->longText('payload');
            $t->unsignedTinyInteger('attempts'); $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at'); $t->unsignedInteger('created_at');
        });
        Schema::create('signage_screens', function (Blueprint $t) {
            $t->id(); $t->string('name',120); $t->string('location',120)->nullable();
            $t->string('token_hash',64)->nullable()->unique(); $t->unsignedBigInteger('release_id')->nullable();
            $t->unsignedBigInteger('active_release_id')->nullable(); $t->timestamp('last_seen_at')->nullable();
            $t->json('status')->nullable(); $t->timestamps();
        });
        Schema::create('signage_pairings', function (Blueprint $t) {
            $t->id(); $t->string('code_hash',64)->unique(); $t->string('nonce_hash',64)->unique();
            $t->dateTime('expires_at'); $t->unsignedBigInteger('screen_id')->nullable();
            $t->text('delivery')->nullable(); $t->timestamps();
        });
        Schema::create('signage_media', function (Blueprint $t) {
            $t->id(); $t->string('name',160); $t->string('original_path'); $t->string('path')->nullable();
            $t->string('poster_path')->nullable(); $t->string('mime',80); $t->string('status',20)->default('queued');
            $t->string('checksum',64)->nullable()->index(); $t->unsignedBigInteger('bytes')->default(0);
            $t->unsignedInteger('width')->nullable(); $t->unsignedInteger('height')->nullable();
            $t->decimal('duration',10,3)->nullable(); $t->boolean('baked_prices')->default(false);
            $t->json('price_snapshot')->nullable(); $t->timestamp('price_valid_until')->nullable();
            $t->string('error',240)->nullable(); $t->timestamps();
        });
        Schema::create('signage_scenes', function (Blueprint $t) { $t->id(); $t->string('name',120); $t->json('content'); $t->timestamps(); });
        Schema::create('signage_playlists', function (Blueprint $t) { $t->id(); $t->string('name',120); $t->json('scene_ids'); $t->timestamps(); });
        Schema::create('signage_releases', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('playlist_id')->index(); $t->json('snapshot');
            $t->unsignedBigInteger('actor_id'); $t->timestamps();
        });
        Schema::create('signage_schedules', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('screen_id')->index(); $t->unsignedBigInteger('release_id');
            $t->json('days'); $t->date('starts_on'); $t->date('ends_on');
            $t->string('start_time',5); $t->string('end_time',5); $t->unsignedInteger('priority')->default(1); $t->timestamps();
        });
        Schema::create('signage_audits', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('actor_id'); $t->string('action',40);
            $t->unsignedBigInteger('screen_id')->nullable(); $t->unsignedBigInteger('release_id')->nullable(); $t->timestamps();
        });
    }
    public function down(): void
    {
        // Data-destructive rollback: disabling the module does not require down().
        foreach (['audits','schedules','releases','playlists','scenes','media','pairings','screens','jobs'] as $table) Schema::dropIfExists('signage_'.$table);
    }
};
