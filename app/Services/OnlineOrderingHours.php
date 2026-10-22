<?php

namespace App\Services;

use App\Models\Settings;
use App\Models\Time;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

class OnlineOrderingHours
{
    public static function minutes(?string $value): ?int
    {
        $value = strtolower(preg_replace('/\s+/', '', trim((string) $value)));
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/D', $value, $parts)) {
            return (int) $parts[1] * 60 + (int) $parts[2];
        }
        if (preg_match('/^(0?[1-9]|1[0-2]):([0-5]\d)(am|pm)$/D', $value, $parts)) {
            return ((int) $parts[1] % 12 + ($parts[3] === 'pm' ? 12 : 0)) * 60 + (int) $parts[2];
        }
        return null;
    }

    public static function validCutoff(?string $open, ?string $close, ?string $cutoff): bool
    {
        $start = self::minutes($open); $end = self::minutes($close); $until = self::minutes($cutoff);
        if ($start === null || $end === null || $until === null) return false;
        if ($end <= $start) $end += 1440;
        if ($until < $start) $until += 1440;
        return $until > $start && $until <= $end;
    }

    public function accepting(): bool
    {
        // Same restaurant switch as the admin header; prefer the owner over staff.
        if ((int) User::whereIn('type', [1, 4])->orderBy('type')->value('is_online') !== 1) return false;
        $timezone = Settings::first()?->timezone ?: config('app.timezone', 'Europe/Budapest');
        return $this->withinSchedule(CarbonImmutable::now($timezone));
    }

    public function withinSchedule(CarbonImmutable $now): bool
    {
        // Compatibility while code and the additive migration are released separately.
        if (!Schema::hasColumn('time', 'online_order_until')) {
            return $now->format('H:i') >= '08:00' && $now->format('H:i') < '21:30';
        }
        $days = Time::all()->keyBy(fn ($row) => strtolower($row->day));
        foreach ([$now->startOfDay(), $now->subDay()->startOfDay()] as $date) {
            $row = $days->get(strtolower($date->format('l')));
            if (!$row || (int) $row->always_close !== 2 || !self::validCutoff($row->open_time, $row->close_time, $row->online_order_until)) continue;
            $startMinute = self::minutes($row->open_time);
            $endMinute = self::minutes($row->online_order_until);
            if ($endMinute < $startMinute) $endMinute += 1440;
            $start = $date->addMinutes($startMinute);
            $end = $date->addMinutes($endMinute);
            if ($now < $start || $now >= $end) continue;
            $breakStart = self::minutes($row->break_start);
            $breakEnd = self::minutes($row->break_end);
            if ($breakStart !== null && $breakEnd !== null && $breakStart !== $breakEnd) {
                if ($breakStart < $startMinute) $breakStart += 1440;
                if ($breakEnd < $startMinute) $breakEnd += 1440;
                if ($breakEnd < $breakStart) $breakEnd += 1440;
                if ($now >= $date->addMinutes($breakStart) && $now < $date->addMinutes($breakEnd)) continue;
            }
            return true;
        }
        return false;
    }
}
