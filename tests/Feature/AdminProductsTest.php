<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\ItemController;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminProductsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'products_test', 'database.connections.products_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'session.driver' => 'array']);
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->integer('type'); });
        Schema::create('languages', function (Blueprint $t) {
            $t->id(); $t->string('code'); $t->string('name'); $t->string('image')->default('');
            $t->integer('layout')->default(1); $t->integer('is_default')->default(1); $t->integer('is_available')->default(1);
        });
        DB::table('languages')->insert(['code' => 'hu', 'name' => 'Magyar']);
        Schema::create('categories', function (Blueprint $t) {
            $t->id(); $t->string('category_name'); $t->string('slug'); $t->string('image')->default('');
            $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2); $t->integer('reorder_id')->default(0);
        });
        foreach ([[1, 'Pizzák', 1, 2], [2, 'Italok', 1, 2], [3, 'Szünetelő kategória', 0, 2], [4, 'Törölt', 1, 1]] as [$id, $name, $available, $deleted]) {
            DB::table('categories')->insert(['id' => $id, 'category_name' => $name, 'slug' => 'category-'.$id,
                'is_available' => $available, 'is_deleted' => $deleted]);
        }
        Schema::create('item', function (Blueprint $t) {
            $t->id(); $t->integer('cat_id')->default(1); $t->integer('subcat_id')->nullable()->default(7);
            $t->string('item_name'); $t->string('slug'); $t->decimal('price')->default(2500); $t->decimal('original_price')->default(0);
            $t->decimal('discount_percentage')->default(0); $t->integer('item_status')->default(1);
            $t->integer('is_featured')->default(2); $t->boolean('today_unavailable')->default(false);
            $t->date('unavailable_date')->nullable(); $t->integer('reorder_id')->default(0);
            $t->text('item_description')->default('Eredeti leírás'); $t->string('addons_id')->default('1,2'); $t->timestamps();
        });
        Schema::create('item_images', function (Blueprint $t) { $t->id(); $t->integer('item_id'); $t->string('image'); });
        Schema::create('cart', function (Blueprint $t) { $t->id(); $t->integer('item_id'); $t->integer('qty'); $t->string('extras_name'); $t->decimal('item_price'); });
        DB::table('users')->insert(['id' => 1, 'name' => 'Tulajdonos', 'type' => 1]);
        $this->actingAs(User::find(1));
    }

    private function item(array $values = []): Item
    {
        $item = new Item();
        $item->forceFill(array_merge(['item_name' => 'Teszt pizza', 'slug' => 'teszt-pizza'], $values))->save();
        return $item->fresh();
    }

    private function payload(Item $item, array $values = []): array
    {
        return array_merge(['id' => $item->id, 'version' => $item->edit_token, 'item_name' => 'Új név',
            'cat_id' => 1, 'price' => 2700, 'original_price' => 3000, 'availability' => 'active', 'is_featured' => true], $values);
    }

    public function test_list_includes_products_in_inactive_categories_and_preserves_menu_order(): void
    {
        $first = $this->item(['reorder_id' => 1]);
        $inactive = $this->item(['cat_id' => 3, 'reorder_id' => 2]);
        $this->item(['cat_id' => 4]);
        $data = (new ItemController())->index(new Request())->getData();
        $this->assertSame([$first->id, $inactive->id], $data['getitem']->pluck('id')->all());
        $this->assertSame([1, 2, 3], $data['categories']->pluck('id')->all());
    }

    public function test_quick_edit_only_changes_catalog_basics_and_keeps_cart_customisations(): void
    {
        $item = $this->item();
        DB::table('cart')->insert(['item_id' => $item->id, 'qty' => 2, 'extras_name' => 'Sajt', 'item_price' => 2500]);
        $this->postJson('/admin/item/quick-update', $this->payload($item, ['cat_id' => 2]))->assertOk()->assertJson(['success' => true]);
        $saved = $item->fresh();
        $this->assertSame('Új név', $saved->item_name);
        $this->assertEquals(2700, $saved->price);
        $this->assertEquals(10, $saved->discount_percentage);
        $this->assertSame(2, $saved->cat_id);
        $this->assertNull($saved->subcat_id);
        $this->assertSame(1, $saved->is_featured);
        $this->assertSame('teszt-pizza', $saved->slug);
        $this->assertSame('Eredeti leírás', $saved->item_description);
        $this->assertSame('1,2', $saved->addons_id);
        $this->assertSame('Sajt', DB::table('cart')->first()->extras_name);
        $this->assertSame(2, DB::table('cart')->first()->qty);
    }

    /** @dataProvider availabilityStates */
    public function test_availability_sets_consistent_orderability_and_daily_reset_flags(string $state, int $status, int $today): void
    {
        $item = $this->item(['item_status' => 0, 'today_unavailable' => true, 'unavailable_date' => '2026-10-20']);
        $this->postJson('/admin/item/quick-update', $this->payload($item, ['availability' => $state]))->assertOk();
        $saved = $item->fresh();
        $this->assertSame($status, $saved->item_status);
        $this->assertEquals($today, $saved->today_unavailable);
        $this->assertSame($today ? now('Europe/Budapest')->toDateString() : null, $saved->unavailable_date);
        $this->assertSame($state === 'active', $saved->is_orderable);
    }

    public static function availabilityStates(): array { return [['active', 1, 0], ['paused', 2, 0], ['today', 0, 1]]; }

    /** @dataProvider invalidEdits */
    public function test_invalid_edits_leave_the_product_untouched(array $values, string $field): void
    {
        $item = $this->item();
        $this->postJson('/admin/item/quick-update', $this->payload($item, $values))->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame('Teszt pizza', $item->fresh()->item_name);
    }

    public static function invalidEdits(): array
    {
        return [[['price' => -1], 'price'], [['original_price' => 100], 'original_price'], [['cat_id' => 4], 'cat_id'],
            [['item_name' => '  '], 'item_name'], [['availability' => 'unknown'], 'availability']];
    }

    public function test_stale_editor_cannot_overwrite_another_saved_change(): void
    {
        $item = $this->item(); $payload = $this->payload($item);
        $this->postJson('/admin/item/quick-update', $payload)->assertOk();
        $this->postJson('/admin/item/quick-update', array_merge($payload, ['item_name' => 'Elavult módosítás']))->assertStatus(409);
        $this->assertSame('Új név', $item->fresh()->item_name);
    }

    public function test_customer_cannot_edit_products_and_keeps_their_login(): void
    {
        DB::table('users')->insert(['id' => 2, 'name' => 'Vásárló', 'type' => 2]);
        $this->actingAs(User::find(2));
        $item = $this->item();
        $this->postJson('/admin/item/quick-update', $this->payload($item))->assertRedirect('/admin');
        $this->assertSame('Teszt pizza', $item->fresh()->item_name);
        $this->assertAuthenticatedAs(User::find(2));
    }

    public function test_reordering_saves_each_position_once_and_rejects_duplicate_ids(): void
    {
        $first = $this->item(); $second = $this->item();
        $this->postJson('/admin/item/reorder_item', ['order' => [['id' => $second->id, 'position' => 1], ['id' => $first->id, 'position' => 2]]])->assertOk();
        $this->assertSame([$second->id, $first->id], Item::orderBy('reorder_id')->pluck('id')->all());
        $this->postJson('/admin/item/reorder_item', ['order' => [['id' => $first->id, 'position' => 1], ['id' => $first->id, 'position' => 2]]])->assertUnprocessable();
        $this->assertSame(2, $first->fresh()->reorder_id);
    }
}
