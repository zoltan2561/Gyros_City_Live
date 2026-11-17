<?php

namespace App\Console\Commands;

use App\Models\SignagePlaylist;
use App\Models\SignageScene;
use App\Services\Signage\SignageCatalog;
use App\Services\Signage\SignageDemoPrograms;
use App\Services\Signage\SignageSceneDefinition;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallSignagePrograms extends Command
{
    protected $signature = 'signage:install-programs {--dry-run : Only report what would be created, save nothing}';

    protected $description = 'Create the seven built-in TV programmes (three video clones, the general advert and three own modern versions) as editable draft lists. Existing lists and scenes are never changed and nothing is published to a TV.';

    private const TAGS = [
        'burger' => 'Videómásolat · burger', 'menu' => 'Videómásolat · étlap', 'pizza' => 'Videómásolat · pizza',
        'modern-burger' => 'Saját modern · burger', 'modern-menu' => 'Saját modern · étlap', 'modern-pizza' => 'Saját modern · pizza',
    ];

    private const LABELS = [
        'reference-burgers' => 'Hat burger', 'modern-burgers' => 'Burgerek',
        'menu-burgers' => 'Burgerek', 'menu-bundles' => 'Burger menük', 'menu-gyros' => 'Gyrosok és kedvencek',
        'menu-fried' => 'Frissensültek', 'menu-sides' => 'Köretek és desszertek', 'menu-kebab' => 'Kebab, döner, dürüm',
        'reference-pizza-intro' => 'Pizza Time nyitókép', 'reference-pizza' => 'Pizzák egyenként', 'modern-pizza' => 'Pizzák egyenként',
        'general-advert' => 'Általános reklám',
    ];

    public function handle(SignageDemoPrograms $demo, SignageCatalog $catalog, SignageSceneDefinition $definition): int
    {
        if (!Schema::hasTable('signage_scenes') || !Schema::hasTable('signage_playlists')) {
            $this->error('A TV-modul táblái nincsenek telepítve. Előbb futtasd a signage migrációkat (docs/signage/deployment.md).');
            return 1;
        }
        $programs = $demo->definitions();
        $bindings = [];
        foreach ($programs as $program) foreach ($program['scenes'] as $scene) foreach ($scene['bindings'] as $binding) $bindings[$binding['id']] = $binding;
        // A dish that is only switched off for now stays in (the TV hides it live); deleted dishes and broken choices cannot.
        $quotes = collect($catalog->snapshot(array_values($bindings), new DateTimeImmutable())['products'])->keyBy('id');
        $dry = (bool) $this->option('dry-run');
        $lists = 0;
        $scenes = 0;
        DB::beginTransaction();
        try {
            foreach ($programs as $program) [$lists, $scenes] = $this->install($program, $quotes, $definition, $lists, $scenes);
            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $this->newLine();
        $this->info(($dry ? 'Próbafutás, semmi sem mentődött. Létrejönne: ' : 'Kész. Létrejött: ') . $lists . ' lista, ' . $scenes . ' jelenet.');
        $this->line('Egyik TV műsora sem változott. A listákat az adminban nézd át, majd publikáld a TV-kre.');
        return 0;
    }

    private function install(array $program, $quotes, SignageSceneDefinition $definition, int $lists, int $scenes): array
    {
        $name = $program['name'];
        if (SignagePlaylist::where('name', $name)->exists()) {
            $this->line('= ' . $name . ': már létezik, nem változtattam.');
            return [$lists, $scenes];
        }
        $this->line('+ ' . $name);
        $ids = [];
        foreach ($program['scenes'] as $scene) {
            $label = self::LABELS[$scene['id']] ?? $scene['id'];
            $sceneName = isset(self::TAGS[$program['id']]) ? self::TAGS[$program['id']] . ' · ' . $label : $label;
            $usable = [];
            foreach ($scene['bindings'] as $binding) {
                $quote = $quotes->get($binding['id']);
                $reason = $quote ? $quote['reason'] : 'missing';   // an orderable dish has reason null
                if ($reason === 'missing' || $reason === 'configuration_required') {
                    $this->warn('    ! ' . $sceneName . ': a(z) #' . $binding['item_id'] . ' étel kimarad (' . ($reason === 'missing' ? 'nincs a menüben' : 'hiányos feltétválasztás') . ').');
                    continue;
                }
                $usable[] = $binding;
            }
            if (!$usable) {
                $this->warn('    ! ' . $sceneName . ': nincs használható étel, a jelenet kimarad.');
                continue;
            }
            $content = $definition->validate(array_replace(array_diff_key($scene, ['id' => true]), ['bindings' => $usable]));
            $record = SignageScene::where('name', $sceneName)->first();
            if (!$record) {
                $record = SignageScene::create(['name' => $sceneName, 'content' => $content]);
                $scenes++;
            }
            $ids[] = $record->id;
        }
        if (!$ids) {
            $this->warn('    ! ' . $name . ': egyetlen jelenet sem készült, a lista kimarad.');
            return [$lists, $scenes];
        }
        SignagePlaylist::create(['name' => $name, 'scene_ids' => $ids]);
        return [$lists + 1, $scenes];
    }
}
