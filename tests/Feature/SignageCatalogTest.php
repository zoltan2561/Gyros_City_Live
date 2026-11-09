<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Services\CatalogPricing;
use App\Services\Signage\SignageCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SignageCatalogTest extends \Tests\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'signage_test', 'database.connections.signage_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('categories', function (Blueprint $t) {
            $t->id(); $t->string('category_name')->default('Burgerek'); $t->string('slug')->default('burger');
            $t->string('image')->default(''); $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2);
        });
        Schema::create('item', function (Blueprint $t) {
            $t->id(); $t->integer('cat_id')->default(1); $t->string('item_name')->default('City Burger');
            $t->string('price')->default('2260'); $t->string('original_price')->default('2500');
            $t->text('item_description')->nullable();
            $t->string('tax')->default('13'); $t->string('addons_id')->nullable();
            $t->integer('item_status')->default(1); $t->boolean('today_unavailable')->default(false);
            $t->integer('is_top_deals')->default(0); $t->timestamps();
        });
        Schema::create('item_images', function (Blueprint $t) { $t->id(); $t->integer('item_id'); $t->string('image'); });
        Schema::create('addons_group', function (Blueprint $t) {
            $t->id(); $t->string('name')->default('Sajt'); $t->integer('selection_type')->default(1);
            $t->integer('selection_count')->default(1); $t->integer('min_count')->default(1); $t->integer('max_count')->default(1);
            $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2);
        });
        Schema::create('addons', function (Blueprint $t) {
            $t->id(); $t->integer('addongroup_id'); $t->string('name'); $t->decimal('price');
            $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2);
        });
        Schema::create('extras', function (Blueprint $t) { $t->id(); $t->integer('item_id'); $t->string('name'); $t->decimal('price'); });
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('timezone')->default('Europe/Budapest');
            foreach (['app_bottom_image','booknow_bg_image','why_choose_image'] as $name) $t->string($name)->nullable();
            $t->string('currency')->default('Ft'); $t->integer('currency_position')->default(2);
            $t->integer('currency_space')->default(1); $t->integer('currency_formate')->default(0); $t->integer('decimal_separator')->default(1);
        });
        Schema::create('systemaddons', function (Blueprint $t) { $t->id(); $t->string('unique_identifier'); $t->integer('activated'); });
        Schema::create('top_deals', function (Blueprint $t) {
            $t->id(); $t->integer('top_deals_switch')->default(1); $t->integer('deal_type')->default(1);
            $t->integer('offer_type')->default(1); $t->decimal('offer_amount')->default(100);
            $t->string('start_date')->default('2026-11-09'); $t->string('end_date')->default('2026-11-09');
            $t->string('start_time')->default('12:00:00'); $t->string('end_time')->default('13:00:00');
        });
        DB::table('categories')->insert(['id' => 1]); DB::table('settings')->insert(['id' => 1]);
        DB::table('item')->insert(['id' => 1]);
        DB::table('systemaddons')->insert(['unique_identifier' => 'top_deals', 'activated' => 1]);
    }

    private function snapshot(array $bindings = [], string $at = '2026-11-09T11:30:00Z'): array
    {
        return app(SignageCatalog::class)->snapshot($bindings ?: [['id' => 'burger', 'item_id' => 1]], new \DateTimeImmutable($at));
    }

    public function test_snapshot_uses_catalog_price_reference_and_backend_currency_without_exposing_item_fields(): void
    {
        $result = $this->snapshot(); $row = $result['products'][0];
        $this->assertSame('2260.00', $row['price']); $this->assertSame('2500.00', $row['reference_price']);
        $this->assertSame('2,260 Ft', $row['formatted_price']); $this->assertTrue($row['available']);
        $this->assertNull($row['image']); $this->assertArrayNotHasKey('tax', $row);
        $this->assertSame('2026-11-09T12:00:00+00:00', $result['valid_until']);
    }

    public function test_tv_ingredients_keep_tooltip_labels_and_remove_definitions_and_html(): void
    {
        DB::table('item')->where('id',1)->update(['item_description'=>'<p>[[Mozzarella|A long cheese explanation]], [[Bazsalikom||Herb tooltip]]</p>']);
        $this->assertSame('Mozzarella, Bazsalikom',$this->snapshot()['products'][0]['description']);
    }

    public function test_local_demo_follows_reference_order_and_skips_unavailable_pizzas(): void
    {
        $this->mock(SignageCatalog::class,function ($mock) {
            $mock->shouldReceive('snapshot')->once()->andReturnUsing(function ($bindings) {
                return ['products'=>array_map(fn($b)=>['id'=>$b['id'],'item_id'=>$b['item_id'],
                    'available'=>!in_array($b['item_id'],[341,338,329],true),'price'=>'2200.00','image'=>'/pizza.jpg'],$bindings)];
            });
        });
        $data=app(\App\Services\Signage\SignageDemoPrograms::class)->build(new \DateTimeImmutable('2026-11-09T12:00:00Z'));
        $pizza=collect($data['programs'])->firstWhere('id','pizza')['scenes'][1];
        $this->assertSame([336,342,339,333,335,334,332],array_column($pizza['bindings'],'item_id'));
        $this->assertSame(140,$pizza['duration']);
        $menu=collect($data['programs'])->firstWhere('id','menu')['scenes'];
        $this->assertCount(6,$menu);$this->assertSame(96,array_sum(array_column($menu,'duration')));
        $this->assertSame([60,61,62,63,65,64],array_column(collect($data['programs'])->firstWhere('id','burger')['scenes'][0]['bindings'],'item_id'));
    }

    /** @dataProvider dealPrices */
    public function test_base_quote_keeps_existing_fixed_and_percentage_deal_rules(int $type, int $amount, string $expected): void
    {
        DB::table('top_deals')->insert(['id' => 1, 'offer_type' => $type, 'offer_amount' => $amount]);
        DB::table('item')->where('id',1)->update(['is_top_deals' => 1]);
        $at = new \DateTimeImmutable('2026-11-09T11:30:00Z');
        $quote = app(CatalogPricing::class)->baseQuote(Item::find(1), $at);
        $row = $this->snapshot()['products'][0];
        $this->assertSame($expected, $row['price']); $this->assertSame((float) $expected, $quote['item_price']);
        $this->assertSame('2026-11-09T12:00:00+00:00', $this->snapshot()['valid_until']);
    }

    public static function dealPrices(): array { return [[1,100,'2160.00'], [1,2260,'2260.00'], [1,3000,'2260.00'], [2,25,'1695.00']]; }

    public function test_time_driven_deal_changes_revision_without_database_writes_and_expires_at_boundary(): void
    {
        DB::table('top_deals')->insert(['id' => 1]); DB::table('item')->where('id',1)->update(['is_top_deals' => 1]);
        $before = $this->snapshot([], '2026-11-09T10:59:59Z');
        $during = $this->snapshot([], '2026-11-09T11:00:00Z');
        $after = $this->snapshot([], '2026-11-09T12:00:01Z');
        $this->assertSame('2026-11-09T11:00:00+00:00', $before['valid_until']);
        $this->assertNotSame($before['catalog_revision'], $during['catalog_revision']);
        $this->assertSame($before['catalog_revision'], $after['catalog_revision']);
        $this->assertSame($during['catalog_revision'], $this->snapshot([], '2026-11-09T11:00:01Z')['catalog_revision']);
    }

    public function test_multi_price_rows_require_valid_complete_choices(): void
    {
        DB::table('addons_group')->insert(['id' => 1]);
        DB::table('addons')->insert(['id' => 11, 'addongroup_id' => 1, 'name' => 'Sajt', 'price' => 250]);
        DB::table('item')->where('id',1)->update(['addons_id' => '1']);
        $result = $this->snapshot([
            ['id' => 'incomplete', 'item_id' => 1],
            ['id' => 'cheese', 'item_id' => 1, 'label' => 'Sajttal', 'addons_id' => '11'],
        ]);
        $this->assertFalse($result['products'][0]['available']); $this->assertNull($result['products'][0]['price']);
        $this->assertSame('configuration_required', $result['products'][0]['reason']);
        $this->assertSame('2510.00', $result['products'][1]['price']);
        $this->assertSame('Sajttal', $result['products'][1]['label']);
    }

    public function test_hidden_paused_and_missing_products_never_return_a_price(): void
    {
        foreach (['today_unavailable' => 1, 'item_status' => 0] as $column => $value) {
            DB::table('item')->where('id',1)->update([$column => $value]);
            $this->assertNull($this->snapshot()['products'][0]['price']);
            DB::table('item')->where('id',1)->update([$column => $column === 'item_status' ? 1 : 0]);
        }
        DB::table('categories')->update(['is_available' => 0]);
        $this->assertFalse($this->snapshot()['products'][0]['available']);
        $this->assertFalse($this->snapshot([['id'=>'missing','item_id'=>999]])['products'][0]['available']);
    }
}
