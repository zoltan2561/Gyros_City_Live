<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'cart' => [
            'gc_cart_session_open_item' => ['session_id', 'buynow', 'item_id'],
            'gc_cart_user_open_item' => ['user_id', 'buynow', 'item_id'],
        ],
        'order_details' => ['gc_order_details_order' => ['order_id']],
        'extras' => ['gc_extras_item' => ['item_id']],
        'item' => ['gc_item_slug' => ['slug']],
        'order' => [
            'gc_order_status_source' => ['status_type', 'order_from', 'id'],
            'gc_order_number' => ['order_number'],
        ],
        'favorite' => ['gc_favorite_user_item' => ['user_id', 'item_id']],
    ];

    private function indexNames(string $table): array
    {
        if (DB::getDriverName() === 'mysql') {
            return array_column(DB::select('SHOW INDEX FROM `'.$table.'`'), 'Key_name');
        }
        return array_column(DB::select('PRAGMA index_list("'.$table.'")'), 'name');
    }

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasTable($table)) continue;
            $existing = $this->indexNames($table);
            foreach ($indexes as $name => $columns) {
                if (in_array($name, $existing, true)) continue;
                if (DB::getDriverName() === 'mysql') {
                    // session_id is TEXT in the live legacy schema. Its prefix narrows
                    // the lookup; MySQL still checks equality against the full value.
                    $sqlColumns = array_map(fn ($column) => '`'.$column.'`'.($column === 'session_id' ? '(64)' : ''), $columns);
                    DB::statement('ALTER TABLE `'.$table.'` ADD INDEX `'.$name.'` ('.implode(',', $sqlColumns).'), ALGORITHM=INPLACE, LOCK=NONE');
                } else {
                    Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasTable($table)) continue;
            $existing = $this->indexNames($table);
            foreach (array_keys($indexes) as $name) {
                if (in_array($name, $existing, true)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
