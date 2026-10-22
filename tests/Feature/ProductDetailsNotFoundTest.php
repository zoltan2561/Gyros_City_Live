<?php

namespace Tests\Feature;

use App\Http\Middleware\MaintenanceMiddleware;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductDetailsNotFoundTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'product_not_found', 'database.connections.product_not_found' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'session.driver' => 'array']);
        $this->withoutMiddleware(MaintenanceMiddleware::class);
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('timezone')->default('Europe/Budapest');
            foreach (['app_bottom_image', 'booknow_bg_image', 'why_choose_image'] as $column) $t->string($column)->nullable();
        });
        DB::table('settings')->insert(['id' => 1]);
        Schema::create('top_deals', fn (Blueprint $t) => $t->id());
        Schema::create('systemaddons', function (Blueprint $t) {
            $t->id(); $t->string('unique_identifier'); $t->integer('activated');
        });
        Schema::create('item', function (Blueprint $t) {
            $t->id(); $t->string('slug'); $t->integer('cat_id'); $t->integer('price');
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id(); $t->integer('is_available'); $t->integer('is_deleted');
        });
        foreach (['cart', 'favorite'] as $table) Schema::create($table, function (Blueprint $t) {
            $t->id(); $t->integer('item_id'); $t->integer('user_id')->nullable();
            $t->string('session_id')->nullable(); $t->integer('buynow')->default(0);
        });
    }

    /** @dataProvider missingProducts */
    public function test_missing_or_hidden_product_returns_404_for_guests_and_customers(bool $signedIn, bool $hidden): void
    {
        if ($signedIn) $this->actingAs((new User())->forceFill(['id' => 42, 'type' => 2]));
        if ($hidden) {
            DB::table('categories')->insert(['id' => 1, 'is_available' => 0, 'is_deleted' => 2]);
            DB::table('item')->insert(['slug' => 'missing-pizza', 'cat_id' => 1, 'price' => 2000]);
        }
        $this->getJson('/item-missing-pizza')->assertNotFound();
    }

    public static function missingProducts(): array
    {
        return [[false, false], [true, false], [false, true], [true, true]];
    }

    public function test_unavailable_product_stays_visible_and_review_counts_reuse_approved_reviews(): void
    {
        Schema::table('settings', fn (Blueprint $t) => $t->integer('reviews_enabled')->default(1));
        DB::table('systemaddons')->insert(['unique_identifier' => 'product_review', 'activated' => 1]);
        Schema::table('categories', function (Blueprint $t) {
            $t->string('category_name')->default('Pizzas'); $t->string('slug')->default('pizzas'); $t->string('image')->nullable();
        });
        Schema::table('item', function (Blueprint $t) {
            $t->integer('subcat_id')->nullable(); $t->string('addons_id')->default('');
            $t->integer('item_status')->default(2); $t->boolean('today_unavailable')->default(true);
        });
        Schema::create('subcategories', function (Blueprint $t) { $t->id(); $t->string('subcategory_name'); $t->string('slug'); });
        Schema::create('item_images', function (Blueprint $t) { $t->id(); $t->integer('item_id'); $t->string('image'); });
        Schema::create('addons_group', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->integer('is_deleted'); $t->integer('is_available');
            foreach (['selection_type', 'selection_count', 'min_count', 'max_count'] as $column) $t->integer($column);
        });
        Schema::create('addons', function (Blueprint $t) {
            $t->id(); $t->integer('addongroup_id'); $t->string('name'); $t->integer('price'); $t->integer('is_deleted'); $t->integer('is_available');
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->integer('type');
            foreach (['name', 'email', 'mobile', 'token', 'profile_image'] as $column) $t->string($column)->nullable();
        });
        Schema::create('ratting', function (Blueprint $t) {
            $t->id(); $t->integer('item_id'); $t->integer('user_id'); $t->integer('ratting');
            $t->integer('status'); $t->string('comment')->default(''); $t->string('name')->nullable(); $t->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'type' => 2]);
        DB::table('categories')->insert(['id' => 1, 'is_available' => 1, 'is_deleted' => 2]);
        DB::table('item')->insert(['id' => 1, 'slug' => 'pizza', 'cat_id' => 1, 'price' => 2000]);
        foreach ([[5, 1], [5, 1], [3, 1], [1, 2]] as [$stars, $status]) {
            DB::table('ratting')->insert(['item_id' => 1, 'user_id' => 1, 'ratting' => $stars, 'status' => $status]);
        }
        DB::enableQueryLog();
        $view = app(\App\Http\Controllers\front\ItemController::class)->itemdetails(new \Illuminate\Http\Request(['slug' => 'pizza']));
        $data = $view->getData();
        $this->assertFalse($data['getitemdata']->is_orderable);
        $this->assertCount(3, $data['itemreviewdata']);
        $this->assertSame(2, $data['fivestaraverage']);
        $this->assertSame(1, $data['threestaraverage']);
        $this->assertSame(0, $data['onestaraverage']);
        $reviewQueries = array_filter(DB::getQueryLog(), fn ($q) => str_contains($q['query'], 'from "ratting"'));
        $this->assertCount(1, $reviewQueries);
    }
}
