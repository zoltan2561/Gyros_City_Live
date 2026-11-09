<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        // Older MariaDB implicitly adds ON UPDATE to the first required TIMESTAMP.
        // Pair confirmation must not reset a ten-minute expiry to the current instant.
        if(DB::getDriverName()==='mysql')DB::statement('ALTER TABLE signage_pairings MODIFY expires_at DATETIME NOT NULL');
    }
    public function down(): void {
        // DATETIME is compatible with the previous application model; preserve it.
    }
};
