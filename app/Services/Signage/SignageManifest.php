<?php
namespace App\Services\Signage;
use App\Models\SignageScreen;
use App\Models\SignageRelease;
use DateTimeImmutable;
use DateTimeZone;
class SignageManifest {
    public function forScreen(int $screenId, DateTimeImmutable $now): array {
        $screen=SignageScreen::findOrFail($screenId);
        $ids=collect($screen->delivered_release_ids??[])->push($screen->release_id)->push($screen->active_release_id)->filter()->unique();
        $releases=SignageRelease::whereIn('id',$ids)->get()->map(fn($r)=>['id'=>$r->id,'snapshot'=>$r->snapshot])->values()->all();
        $content=['playback_mode'=>'continuous','default_release_id'=>$screen->release_id,'releases'=>$releases,'schedules'=>[]];
        return ['schema_version'=>1,'player_version'=>'1','layout_revision'=>hash('sha256',json_encode($content)),
            'server_time'=>$now->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
            'schedule_valid_until'=>$now->modify('+7 days')->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
            'poll_seconds'=>30,'heartbeat_seconds'=>60]+$content;
    }
    public function bindings(array $manifest): array {
        $bindings=[];
        foreach($manifest['releases'] as $release) foreach($release['snapshot']['bindings']??[] as $row) $bindings[$row['id']]=$row;
        return array_values($bindings);
    }
    public function media(array $manifest): array {
        $media=[];
        foreach($manifest['releases'] as $release) foreach($release['snapshot']['media']??[] as $row) $media[$row['checksum']]=$row;
        return array_values($media);
    }
}
