<?php

namespace Tests\Feature;

use App\Helpers\helper;
use App\Http\Middleware\MaintenanceMiddleware;
use App\Models\Item;
use App\Models\Ratting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewPortTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'reviews_test', 'database.connections.reviews_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'session.driver' => 'array', 'app.key' => 'base64:'.base64_encode(str_repeat('r', 32))]);
        $this->withoutMiddleware(MaintenanceMiddleware::class);
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->integer('review_approved_status')->default(2); $t->string('review_auto_approved')->default('');
            $t->string('google_review_url')->nullable();
            foreach (['app_bottom_image', 'booknow_bg_image', 'why_choose_image'] as $column) $t->string($column)->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->default('Test'); $t->integer('type')->default(2);
            $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2); $t->integer('role_id')->default(1);
        });
        Schema::create('manage_roles', function (Blueprint $t) {
            $t->id(); $t->string('modules'); $t->integer('is_available')->default(1);
        });
        Schema::create('languages', function (Blueprint $t) {
            $t->id(); $t->string('name')->default('Hungarian'); $t->string('code')->default('hu');
            $t->string('image')->default(''); $t->integer('layout')->default(1); $t->integer('is_default')->default(1); $t->integer('is_available')->default(1);
        });
        DB::table('languages')->insert(['id' => 1]);
        Schema::create('systemaddons', function (Blueprint $t) {
            $t->id(); $t->string('unique_identifier'); $t->integer('activated')->default(2);
            $t->timestamps();
        });
        foreach (['product_review', 'store_review'] as $kind) DB::table('systemaddons')->insert(['unique_identifier' => $kind]);
        Schema::create('item', function (Blueprint $t) {
            $t->id(); $t->string('item_name')->default('Test pizza'); $t->integer('item_status')->default(1); $t->decimal('avg_ratting')->default(0); $t->timestamps();
        });
        DB::table('item')->insert([['id' => 1], ['id' => 2]]);
        Schema::create('ratting', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->integer('item_id')->nullable(); $t->integer('ratting');
            $t->string('comment')->default(''); $t->string('name')->nullable(); $t->string('image')->nullable();
            $t->integer('status')->default(2); $t->integer('reorder_id')->default(0); $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_22_000001_add_reviews_enabled_to_settings.php'))->up();
        DB::table('settings')->insert(['id' => 1]);
    }

    private function customer(array $attributes = []): User
    {
        return User::findOrFail(DB::table('users')->insertGetId($attributes));
    }

    private function enable(): void
    {
        DB::table('settings')->update(['reviews_enabled' => 1]);
        DB::table('systemaddons')->update(['activated' => 1]);
    }

    public function test_migration_and_missing_setting_both_default_to_disabled(): void
    {
        $this->assertSame(2, (int) DB::table('settings')->value('reviews_enabled'));
        DB::table('systemaddons')->update(['activated' => 1]);
        $this->assertFalse(helper::reviews_enabled());
        Schema::table('settings', fn (Blueprint $t) => $t->dropColumn('reviews_enabled'));
        $this->assertFalse(helper::reviews_enabled('product_review'));
    }

    public function test_disabled_module_blocks_direct_submission_without_deleting_earlier_reviews(): void
    {
        $customer = $this->customer();
        Ratting::create(['user_id' => $customer->id, 'item_id' => 1, 'ratting' => 5, 'status' => 1]);
        $this->actingAs($customer)->postJson('/add-review', ['item_id' => 2, 'ratting' => 4])->assertForbidden();
        $this->assertSame(1, Ratting::count());
        $this->assertSame(0, Ratting::publicReviews()->count());
        $this->assertSame(0.0, Item::find(1)->avg_ratting);
    }

    public function test_stars_are_required_and_comments_are_optional(): void
    {
        $this->enable();
        $this->actingAs($this->customer())->postJson('/add-review', ['item_id' => 1, 'comment' => 'Text only'])->assertUnprocessable();
        $this->postJson('/add-review', ['item_id' => 1, 'ratting' => 4])->assertRedirect();
        $review = Ratting::firstOrFail();
        $this->assertSame('', $review->comment);
        $this->assertSame(2, (int) $review->status);
    }

    public function test_a_customer_can_review_each_product_and_the_restaurant_once(): void
    {
        $this->enable(); $this->actingAs($this->customer());
        foreach ([1, 2, null] as $id) {
            $data = ['item_id' => $id, 'ratting' => 5];
            $this->postJson('/add-review', $data)->assertRedirect();
            $this->postJson('/add-review', $data)->assertUnprocessable();
        }
        $this->assertSame(3, Ratting::count());
    }

    public function test_invalid_rating_item_and_oversized_comment_do_not_write(): void
    {
        $this->enable(); $this->actingAs($this->customer());
        foreach ([['ratting' => 0], ['ratting' => 6], ['ratting' => 4, 'item_id' => 999],
            ['ratting' => 4, 'comment' => str_repeat('a', 256)]] as $data) {
            $this->postJson('/add-review', $data)->assertUnprocessable();
        }
        $this->assertSame(0, Ratting::count());
    }

    public function test_anonymous_staff_and_disabled_customer_cannot_submit(): void
    {
        $this->enable();
        $this->post('/add-review', ['ratting' => 5])->assertRedirect('/login');
        foreach ([['type' => 4], ['is_available' => 2], ['is_deleted' => 1]] as $attributes) {
            $this->actingAs($this->customer($attributes))->post('/add-review', ['ratting' => 5])->assertRedirect('/login');
        }
        $this->assertSame(0, Ratting::count());
    }

    public function test_owner_can_enable_and_disable_without_erasing_moderation_choices(): void
    {
        DB::table('settings')->update(['review_auto_approved' => '5']);
        $this->actingAs($this->customer(['type' => 1]))
            ->post('/admin/review-settings/update', ['reviews_enabled' => 1])->assertRedirect();
        $this->assertTrue(helper::reviews_enabled('product_review'));
        $this->post('/admin/review-settings/update', [])->assertRedirect();
        $this->assertFalse(helper::reviews_enabled());
        $this->assertSame('5', DB::table('settings')->value('review_auto_approved'));
    }

    public function test_operator_can_moderate_but_cannot_change_the_master_switch(): void
    {
        DB::table('manage_roles')->insert(['id' => 1, 'modules' => '29']);
        $customer = $this->customer();
        $review = Ratting::create(['user_id' => $customer->id, 'item_id' => 1, 'ratting' => 4]);
        $this->actingAs($this->customer(['type' => 4]));
        $this->postJson('/admin/review-settings/update', ['reviews_enabled' => 1])->assertForbidden();
        $this->post('/admin/reviews/status', ['id' => $review->id, 'status' => 1])->assertRedirect();
        $this->assertSame(1, (int) $review->fresh()->status);
    }

    public function test_only_approved_customer_product_reviews_affect_averages(): void
    {
        $this->enable(); $customer = $this->customer();
        $review = Ratting::create(['user_id' => $customer->id, 'item_id' => 1, 'ratting' => 4, 'status' => 2]);
        $this->assertSame(0.0, Item::find(1)->avg_ratting);
        $review->update(['status' => 1]);
        Ratting::create(['user_id' => $this->customer(['type' => 1])->id, 'ratting' => 5, 'name' => 'Featured quote', 'status' => 1]);
        $this->assertSame(4.0, Item::find(1)->avg_ratting);
        $this->assertSame(1, Ratting::publicReviews()->count());
        $review->delete();
        $this->assertSame(0.0, Item::find(1)->avg_ratting);
    }

    public function test_auto_approval_only_publishes_selected_star_counts(): void
    {
        $this->enable(); DB::table('settings')->update(['review_approved_status' => 1, 'review_auto_approved' => '5']);
        $this->actingAs($this->customer());
        $this->post('/add-review', ['item_id' => 1, 'ratting' => 5])->assertRedirect();
        $this->post('/add-review', ['item_id' => 2, 'ratting' => 3])->assertRedirect();
        $this->assertSame(1, (int) Ratting::where('item_id', 1)->value('status'));
        $this->assertSame(2, (int) Ratting::where('item_id', 2)->value('status'));
    }

    public function test_customer_reviews_and_manually_added_quotes_are_separate(): void
    {
        $customer = $this->customer();
        $review = Ratting::create(['user_id' => $customer->id, 'item_id' => 1, 'ratting' => 4, 'status' => 2]);
        $owner = $this->customer(['type' => 1]);
        $quote = Ratting::create(['user_id' => $owner->id, 'ratting' => 5, 'name' => 'Featured quote', 'status' => 1]);
        $this->actingAs($owner)->postJson('/admin/reviews/destroy', ['id' => $quote->id])->assertNotFound();
        $this->postJson('/admin/store-review/update-'.$review->id,
            ['name' => 'Changed', 'ratting' => 1, 'comment' => 'Changed'])->assertNotFound();
        $this->assertSame(2, Ratting::count());
        $this->assertSame([$quote->id], Ratting::featuredTestimonials()->pluck('id')->all());
    }

    public function test_a_disabled_addon_and_invalid_settings_are_enforced(): void
    {
        $this->enable(); DB::table('systemaddons')->where('unique_identifier', 'product_review')->update(['activated' => 2]);
        $this->actingAs($this->customer())->postJson('/add-review', ['item_id' => 1, 'ratting' => 5])->assertForbidden();
        $this->actingAs($this->customer(['type' => 1]));
        foreach ([['reviews_enabled' => 9], ['review_auto_approved' => [6]], ['review_auto_approved' => '5']] as $data) {
            $this->postJson('/admin/review-settings/update', $data)->assertUnprocessable();
        }
        $this->assertSame(1, (int) DB::table('settings')->value('reviews_enabled'));
    }

    public function test_google_invitation_is_independent_of_the_disabled_internal_reviews(): void
    {
        DB::table('settings')->update(['google_review_url' => 'https://g.page/r/test/review']);
        $html = view('web.city.google-review')->render();
        $this->assertFalse(helper::reviews_enabled());
        $this->assertStringContainsString('https://g.page/r/test/review', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringNotContainsString('fa-star', $html);
        DB::table('settings')->update(['google_review_url' => 'javascript:alert(1)']);
        $this->assertStringNotContainsString('<section', view('web.city.google-review')->render());
    }

    public function test_guest_review_button_opens_a_login_prompt_only_when_reviews_are_enabled(): void
    {
        $this->app->setLocale('hu');
        $getitemdata = Item::findOrFail(1);
        $this->assertSame('', trim(view('web.partials.product-review-action', compact('getitemdata'))->render()));
        $this->assertSame('', trim(view('web.partials.review-login-modal')->render()));
        $this->enable();
        $action = view('web.partials.product-review-action', compact('getitemdata'))->render();
        $this->assertStringContainsString('data-bs-target="#review-login-modal"', $action);
        $this->assertStringContainsString('Étel értékelése', $action);
        $this->assertStringNotContainsString('name="ratting"', $action);
        $modal = view('web.partials.review-login-modal')->render();
        $this->assertStringContainsString('Az értékeléshez jelentkezz be', $modal);
        $this->assertStringContainsString('href="http://localhost/login"', $modal);
        $this->post('/add-review', ['item_id'=>1,'ratting'=>5])->assertRedirect('/login');
        $this->assertSame(0, Ratting::count());
    }

    public function test_empty_states_show_contextual_text_instead_of_the_old_placeholder_image(): void
    {
        $this->app->setLocale('hu');
        foreach (['empty_cart','empty'] as $key) {
            $html = view('web.nodata', ['emptyTitle'=>trans('reviews.'.$key)])->render();
            $this->assertStringContainsString(trans('reviews.'.$key), $html);
            $this->assertStringNotContainsString('<img', $html);
        }
        $this->assertStringContainsString('Jelenleg nincs megjeleníthető tartalom.', view('web.nodata')->render());
    }
}
