<?php
namespace Tests\Feature;

use App\Models\SignagePlaylist;
use App\Models\SignageRelease;
use App\Models\SignageScene;
use App\Models\SignageScreen;
use App\Services\Signage\SignageSceneDefinition;
use Illuminate\Support\Facades\DB;

class SignageInstallProgramsTest extends SignageCatalogTest {
    protected function setUp(): void {
        parent::setUp();
        (require base_path('database/migrations/2026_11_09_000001_create_signage_tables.php'))->up();
        // The shop only has a few of the dishes the built-in programmes mention.
        foreach ([60 => 'City Burger', 61 => 'City Burger Plusz', 336 => "'Nduja pizza", 342 => 'Bufala pizza'] as $id => $name)
            DB::table('item')->insert(['id' => $id, 'item_name' => $name, 'price' => '2500', 'item_description' => 'Paradicsomos alap, mozzarella.']);
    }

    public function test_the_built_in_programmes_become_editable_draft_lists_without_touching_any_tv(): void {
        $screen = SignageScreen::create(['name' => 'Pult TV']);
        $this->artisan('signage:install-programs')->assertExitCode(0);
        $names = SignagePlaylist::orderBy('id')->pluck('name')->all();
        $this->assertSame(['Burger · az eredeti videó alapján', 'Étlap · az eredeti videó alapján', 'Pizza · az eredeti videó alapján',
            'Általános reklám', 'Burger · saját modern változat', 'Étlap · saját modern változat', 'Pizza · saját modern változat'], $names);
        $this->assertSame(0, SignageRelease::count());
        $this->assertNull($screen->fresh()->release_id);
        foreach (SignagePlaylist::all() as $list) {
            $this->assertNotEmpty($list->scene_ids);
            foreach ($list->scene_ids as $id) $this->assertNotNull(SignageScene::find($id));
        }
    }

    public function test_stored_scenes_are_valid_and_only_keep_dishes_that_exist_on_the_menu(): void {
        $this->artisan('signage:install-programs')->assertExitCode(0);
        $this->assertGreaterThan(0, SignageScene::count());
        foreach (SignageScene::all() as $scene) {
            app(SignageSceneDefinition::class)->validate($scene->content);
            foreach ($scene->content['bindings'] as $binding) $this->assertContains($binding['item_id'], [60, 61, 336, 342], $scene->name);
        }
        $pizza = SignageScene::where('name', 'Videómásolat · pizza · Pizzák egyenként')->firstOrFail();
        $this->assertSame('reference-pizza', $pizza->content['design']);
        $this->assertSame('slideshow', $pizza->content['type']);
        $this->assertSame([336, 342], array_column($pizza->content['bindings'], 'item_id'));
    }

    public function test_a_dish_switched_off_today_stays_in_the_scene_because_it_returns_by_itself(): void {
        DB::table('item')->where('id', 61)->update(['today_unavailable' => 1]);
        $this->artisan('signage:install-programs')->assertExitCode(0);
        $scene = SignageScene::where('name', 'Videómásolat · burger · Hat burger')->firstOrFail();
        $this->assertContains(61, array_column($scene->content['bindings'], 'item_id'));
    }

    public function test_running_it_again_creates_nothing_and_never_overwrites_edits(): void {
        $this->artisan('signage:install-programs')->assertExitCode(0);
        $lists = SignagePlaylist::count();
        $scenes = SignageScene::count();
        $list = SignagePlaylist::where('name', 'Általános reklám')->firstOrFail();
        $list->update(['scene_ids' => []]);
        $this->artisan('signage:install-programs')->assertExitCode(0);
        $this->assertSame($lists, SignagePlaylist::count());
        $this->assertSame($scenes, SignageScene::count());
        $this->assertSame([], $list->fresh()->scene_ids);
    }

    public function test_with_the_whole_menu_present_all_programmes_are_installed_and_every_list_can_be_published(): void {
        $this->mock(\App\Services\Signage\SignageCatalog::class, function ($mock) {
            $mock->shouldReceive('snapshot')->andReturnUsing(fn ($bindings) => ['products' => array_map(fn ($b) => [
                'id' => (string) $b['id'], 'item_id' => $b['item_id'], 'name' => 'Étel', 'available' => true, 'reason' => null,
                'price' => '2200.00', 'formatted_price' => '2 200 Ft', 'image' => null, 'description' => '', 'label' => '',
            ], $bindings)]);
        });
        $this->artisan('signage:install-programs')->assertExitCode(0);
        $this->assertSame(7, SignagePlaylist::count());
        $this->assertSame(18, SignageScene::count());
        foreach (SignagePlaylist::all() as $list) {
            $snapshot = app(\App\Services\Signage\SignagePublisher::class)->build($list->id);
            $this->assertCount(count($list->scene_ids), $snapshot['scenes'], $list->name);
        }
    }

    public function test_dry_run_reports_without_saving_anything(): void {
        $this->artisan('signage:install-programs', ['--dry-run' => true])->assertExitCode(0);
        $this->assertSame(0, SignagePlaylist::count());
        $this->assertSame(0, SignageScene::count());
    }
}
